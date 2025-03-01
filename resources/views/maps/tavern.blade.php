<svg
    width="{{ $dimensions->width * 60 }}"
    height="{{ $dimensions->height * 60 }}"
    viewBox="0 0 1400 900"
    xmlns="http://www.w3.org/2000/svg"
    xmlns:xlink="http://www.w3.org/1999/xlink"
>
    <defs>
        <pattern id="texture-floor" patternUnits="userSpaceOnUse" width="100" height="100">
            <image xlink:href="/textures/{{ $flooring_tile }}" x="0" y="0" width="100" height="100" />
        </pattern>
        @foreach($objects as $floorObject)
            <pattern
                id="texture-{{ $floorObject->id }}"
                patternUnits="userSpaceOnUse"
                width="{{ $floorObject->texture_width }}"
                height="{{ $floorObject->texture_height }}"
            >
                <image
                    xlink:href="/textures/{{ $floorObject->texture }}"
                    x="0"
                    y="0"
                    width="{{ $floorObject->texture_width }}"
                    height="{{ $floorObject->texture_height }}"
                />
            </pattern>
        @endforeach
    </defs>

    <g stroke="#000" fill="transparent" id="floorTexture">
        <rect x="0" y="0" width="{{ $dimensions->width * 100 }}" height="{{ $dimensions->height * 100 }}" fill="url(#texture-floor)"/>
    </g>
    <g stroke="#000" fill="transparent" id="floorCarpets">
        @foreach($objects as $floorObject)
            @if ($floorObject->z_height === 'floor')
                <rect x="{{ $floorObject->x * 100 }}" y="{{ $floorObject->y * 100 }}"
                      width="{{ $floorObject->width * 100 }}" height="{{ $floorObject->height * 100 }}"
                      fill="url(#texture-{{ $floorObject->id }})"
                />
            @endif
        @endforeach
    </g>
    <g stroke="#000" fill="transparent" id="floorGrid">
        @for ($x = 0; $x < $dimensions->width; $x ++)
            @for ($y = 0; $y < $dimensions->height; $y ++)
                <rect x="{{ $x * 100 }}" y="{{ $y * 100 }}" width="100" height="100" />
            @endfor
        @endfor
    </g>

    <g fill="transparent" id="tables">
        @foreach($objects as $floorObject)
            @if ($floorObject->z_height === 'table')
                <g transform="translate({{ $floorObject->x * 100 }},{{ $floorObject->y * 100 }})">
                    <rect x="0" y="0"
                        width="{{ $floorObject->width * 100 }}" height="{{ $floorObject->height * 100 }}"
                        fill="url(#texture-{{ $floorObject->id }})"
                        stroke-width2="3"
                        stroke2="red"
                    >
                        @if ($floorObject->name_visible === 1)
                            <title>{{ $floorObject->name }}</title>
                        @endif
                    </rect>
                </g>
            @endif
        @endforeach
    </g>
</svg>
