<div class="id-card">
    <table class="id-card-table">
        <tr>
            <td class="header-cell" colspan="3">
                <table class="header-table">
                    <tr>
                        <td class="logo-cell">
                            @if(!empty($school['logo_absolute_path']))
                                <img src="{{ $school['logo_absolute_path'] }}" alt="School Logo" class="logo">
                            @endif
                        </td>
                        <td class="title-cell">
                            <p class="school-name">{{ $school['name'] }}</p>
                            <p class="sub-title">Student ID Card</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="photo-cell">
                @if(!empty($card['photo_absolute_path']))
                    <img src="{{ $card['photo_absolute_path'] }}" alt="Student Photo" class="photo">
                @else
                    <div class="photo-placeholder">PHOTO</div>
                @endif
            </td>
            <td class="meta-cell">
                <p class="meta-row"><span class="label">Name:</span> {{ $card['name'] }}</p>
                <p class="meta-row"><span class="label">Father:</span> {{ $card['father_name'] }}</p>
                <p class="meta-row"><span class="label">Class:</span> {{ $card['class_name'] }}</p>
                <p class="meta-row"><span class="label">Student ID:</span> {{ $card['student_id'] }}</p>
            </td>
            <td class="qr-cell">
                <img src="{{ $card['qr_data_uri'] }}" alt="QR Code" class="qr">
                <p class="meta-row" style="margin-top:6px; font-size:10px; text-align:center;">Scan to view profile</p>
            </td>
        </tr>
    </table>
</div>
