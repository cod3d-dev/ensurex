@props([
    'bgColor' => 'gray',
    'textColor' => 'dark',
    'textShade' => '800',
    'bgShade' => '50',
    'borderShade' => '200',
    'size' => 'text-sm'
])

<span class="inline-flex items-center rounded-md bg-{{ $bgColor }}-{{ $bgShade }} text-{{ $textColor }}-{{ $textShade }} px-2 py-1 {{ $size }} font-medium border  ring-inset">{{ $slot }}</span>
