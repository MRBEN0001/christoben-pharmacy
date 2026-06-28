@if($setting && $setting->nama_perusahaan)
    @if(($variant ?? 'block') === 'receipt-small')
        <h3 style="margin-bottom: 5px;">{{ strtoupper($setting->nama_perusahaan) }}</h3>
    @else
        <h1 style="margin: 5px 0; font-size: {{ $nameSize ?? '24px' }}; font-weight: bold;">{{ strtoupper($setting->nama_perusahaan) }}</h1>
    @endif
@endif
@if(!empty($setting->deskripsi_perusahaan))
    <p style="margin: 4px 0; {{ $descriptionStyle ?? '' }}">{{ $setting->deskripsi_perusahaan }}</p>
@endif
@if(!empty($showAddress) && !empty($setting->alamat))
    <p style="margin: 4px 0; {{ $addressStyle ?? '' }}">{{ ($uppercaseAddress ?? false) ? strtoupper($setting->alamat) : $setting->alamat }}</p>
@endif
@if(!empty($showAddress) && !empty($setting->telepon))
    <p style="margin: 4px 0; {{ $phoneStyle ?? '' }}">{{ ($uppercasePhone ?? false) ? strtoupper($setting->telepon) : $setting->telepon }}</p>
@endif
