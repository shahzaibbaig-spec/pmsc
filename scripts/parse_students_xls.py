import json
import re
import sys

import xlrd


def normalize_header(value: str) -> str:
    value = (value or "").strip().lower()
    value = re.sub(r"[^a-z0-9]+", "_", value)
    return value.strip("_")


def as_text(cell, workbook_datemode: int, header_key: str) -> str:
    ctype = cell.ctype
    value = cell.value

    if ctype == xlrd.XL_CELL_EMPTY:
        return ""

    if ctype == xlrd.XL_CELL_DATE:
        try:
            return xlrd.xldate_as_datetime(value, workbook_datemode).date().isoformat()
        except Exception:
            return ""

    if ctype == xlrd.XL_CELL_NUMBER:
        if "date" in header_key and value >= 20000:
            try:
                return xlrd.xldate_as_datetime(value, workbook_datemode).date().isoformat()
            except Exception:
                pass

        if float(value).is_integer():
            return str(int(value))
        return str(value).strip()

    if ctype == xlrd.XL_CELL_BOOLEAN:
        return "1" if bool(value) else "0"

    return str(value).strip()


def main() -> int:
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Path is required"}))
        return 1

    path = sys.argv[1]
    sheet_index = int(sys.argv[2]) if len(sys.argv) > 2 else 0

    workbook = xlrd.open_workbook(path)
    sheet = workbook.sheet_by_index(sheet_index)

    headers = []
    for c in range(sheet.ncols):
        raw = str(sheet.cell_value(0, c)).strip()
        headers.append(normalize_header(raw))

    rows = []
    for r in range(1, sheet.nrows):
        row = {}
        has_value = False

        for c in range(sheet.ncols):
            header = headers[c] if c < len(headers) else ""
            if header == "":
                continue

            value = as_text(sheet.cell(r, c), workbook.datemode, header)
            row[header] = value
            has_value = has_value or value != ""

        if has_value:
            rows.append(row)

    print(json.dumps({"rows": rows}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

