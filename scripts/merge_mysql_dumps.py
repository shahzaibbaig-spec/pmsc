#!/usr/bin/env python3
"""
Merge two MySQL dump files without losing data from either dump.

Strategy:
- Keep the primary dump unchanged (current Laravel schema/data).
- Rename every table in the secondary dump to `legacy_<table_name>`.
- Append transformed secondary dump after the primary dump.

This avoids schema collisions while preserving all legacy rows.
"""

from __future__ import annotations

import argparse
import re
from pathlib import Path


CREATE_TABLE_RE = re.compile(r"(?im)^\s*CREATE\s+TABLE\s+`([^`]+)`")


def read_text(path: Path) -> str:
    return path.read_text(encoding="utf-8", errors="replace")


def extract_table_names(sql: str) -> list[str]:
    names = CREATE_TABLE_RE.findall(sql)
    seen: set[str] = set()
    ordered: list[str] = []
    for name in names:
        if name not in seen:
            seen.add(name)
            ordered.append(name)
    return ordered


def transform_secondary_dump(secondary_sql: str, prefix: str) -> tuple[str, dict[str, str]]:
    source_tables = extract_table_names(secondary_sql)
    mapping = {name: f"{prefix}{name}" for name in source_tables}

    transformed = secondary_sql
    # Replace longest names first to avoid partial-overlap edge cases.
    for old_name in sorted(mapping.keys(), key=len, reverse=True):
        transformed = transformed.replace(f"`{old_name}`", f"`{mapping[old_name]}`")

    return transformed, mapping


def strip_embedded_html_errors(secondary_sql: str) -> str:
    # Some phpMyAdmin exports can contain embedded HTML error blocks at the end.
    return re.sub(r"(?is)\n<div class=\"error\">.*$", "\n", secondary_sql)


def make_secondary_inserts_ignore(secondary_sql: str) -> str:
    return re.sub(r"(?im)^(\s*)INSERT\s+INTO\s+`", r"\1INSERT IGNORE INTO `", secondary_sql)


def ensure_trailing_commit(secondary_sql: str) -> str:
    if re.search(r"(?im)^\s*COMMIT\s*;\s*$", secondary_sql):
        return secondary_sql

    return secondary_sql.rstrip() + "\n\nCOMMIT;\n"


def build_merged_dump(primary_sql: str, transformed_secondary_sql: str, mapping: dict[str, str]) -> str:
    lines = [
        "-- --------------------------------------------------------",
        "-- MERGED SQL DUMP",
        "-- Primary dump preserved as-is.",
        "-- Secondary dump imported into legacy-prefixed tables.",
        f"-- Legacy table count: {len(mapping)}",
        "-- --------------------------------------------------------",
        "",
        primary_sql.rstrip(),
        "",
        "-- --------------------------------------------------------",
        "-- LEGACY DATA (renamed tables from secondary dump)",
        "-- --------------------------------------------------------",
        transformed_secondary_sql.rstrip(),
        "",
    ]

    return "\n".join(lines)


def write_mapping_report(report_path: Path, mapping: dict[str, str]) -> None:
    rows = ["old_table,new_table"]
    for old_name in sorted(mapping):
        rows.append(f"{old_name},{mapping[old_name]}")
    report_path.write_text("\n".join(rows) + "\n", encoding="utf-8")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Merge two MySQL dumps with lossless legacy table preservation.")
    parser.add_argument("--primary", required=True, help="Path to primary SQL dump (kept unchanged).")
    parser.add_argument("--secondary", required=True, help="Path to secondary SQL dump (renamed to legacy_* tables).")
    parser.add_argument("--output", required=True, help="Path to merged SQL output file.")
    parser.add_argument(
        "--report",
        required=False,
        help="Optional CSV mapping report path (old_table,new_table).",
    )
    parser.add_argument(
        "--legacy-prefix",
        default="legacy_",
        help="Prefix for secondary dump tables (default: legacy_).",
    )
    parser.add_argument(
        "--secondary-output",
        required=False,
        help="Optional output path for transformed legacy-only SQL.",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    primary_path = Path(args.primary).expanduser().resolve()
    secondary_path = Path(args.secondary).expanduser().resolve()
    output_path = Path(args.output).expanduser().resolve()
    report_path = Path(args.report).expanduser().resolve() if args.report else None
    secondary_output_path = Path(args.secondary_output).expanduser().resolve() if args.secondary_output else None

    primary_sql = read_text(primary_path)
    secondary_sql = read_text(secondary_path)
    secondary_sql = strip_embedded_html_errors(secondary_sql)
    transformed_secondary_sql, mapping = transform_secondary_dump(secondary_sql, args.legacy_prefix)
    transformed_secondary_sql = make_secondary_inserts_ignore(transformed_secondary_sql)
    transformed_secondary_sql = ensure_trailing_commit(transformed_secondary_sql)
    merged_sql = build_merged_dump(primary_sql, transformed_secondary_sql, mapping)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(merged_sql, encoding="utf-8")

    if report_path is not None:
        report_path.parent.mkdir(parents=True, exist_ok=True)
        write_mapping_report(report_path, mapping)

    if secondary_output_path is not None:
        secondary_output_path.parent.mkdir(parents=True, exist_ok=True)
        secondary_output_path.write_text(transformed_secondary_sql, encoding="utf-8")

    print(f"Primary dump:   {primary_path}")
    print(f"Secondary dump: {secondary_path}")
    print(f"Merged output:  {output_path}")
    print(f"Legacy tables:  {len(mapping)}")
    if report_path is not None:
        print(f"Mapping report: {report_path}")
    if secondary_output_path is not None:
        print(f"Legacy SQL:     {secondary_output_path}")


if __name__ == "__main__":
    main()
