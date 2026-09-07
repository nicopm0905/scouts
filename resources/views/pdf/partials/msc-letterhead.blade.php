{{--
    Membrete oficial de la Delegación Diocesana del MSC, el mismo que llevan los
    impresos en papel: logo del grupo centrado arriba, los dos sellos y el texto
    legal en vertical en el margen izquierdo, y los datos de la delegación al pie.

    Todo va con `position: fixed` para que se repita en cada página. La hoja que
    incluya este parcial tiene que reservar el hueco con los márgenes de @page:
    unos 53mm arriba, 28mm a la izquierda y 15mm abajo.

    En apaisado (`$mscLandscape = true`) se deja solo el logo y el pie: el texto
    legal en vertical y los sellos laterales están medidos para A4 en vertical.
--}}
@php $mscLandscape = $mscLandscape ?? false; @endphp
@php
    $mscLogo = function (string $file): ?string {
        $path = public_path('images/'.$file);

        return file_exists($path)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($path))
            : null;
    };

    $asidoniaData = $mscLogo('logo-asidonia-jerez.png');
    $andaluciaData = $mscLogo('logo-andalucia.png');
    $mscData = $mscLogo('logo-msc.png');
@endphp

<style>
    .msc-head { position: fixed; top: {{ $mscLandscape ? '-26mm' : '-47mm' }}; left: 0; right: 0; text-align: center; }
    .msc-head img { height: {{ $mscLandscape ? '78px' : '162px' }}; }

    .msc-logo-top { position: fixed; left: -24mm; top: 4mm; width: 40px; }
    .msc-logo-bottom { position: fixed; left: -24mm; bottom: 6mm; width: 44px; }

    /* Texto legal girado 90º pegado al margen izquierdo. */
    .msc-sidebar {
        position: fixed;
        left: -21mm;
        top: 212mm;
        width: 230mm;
        transform: rotate(-90deg);
        transform-origin: left top;
        font-size: 6pt;
        color: #222;
        line-height: 1.15;
    }

    .msc-foot {
        position: fixed;
        bottom: -13mm;
        left: 0;
        right: 0;
        text-align: center;
        color: #111;
        line-height: 1.3;
    }
    .msc-foot .org { font-weight: bold; font-size: 10pt; }
    .msc-foot .addr { font-size: 7pt; }
</style>

@unless($mscLandscape)
    @if($andaluciaData)
        <img src="{{ $andaluciaData }}" class="msc-logo-top" alt="Scouts Católicos Andalucía" />
    @endif

    @if($mscData)
        <img src="{{ $mscData }}" class="msc-logo-bottom" alt="Movimiento Scout Católico" />
    @endif

    <div class="msc-sidebar">
        Asociación inscrita en el registro de Entidades Religiosas del Ministerio de Justicia con el nº 1898-SE/ C CIF: G-11611613<br>
        Aprobado por el pleno de la Conferencia Episcopal Española el 6-VII-1973. Miembro del Movimiento Scout Católico.
    </div>
@endunless

<div class="msc-head">
    @if($asidoniaData)
        <img src="{{ $asidoniaData }}" alt="Scouts Católicos Asidonia-Jerez" />
    @endif
</div>

<div class="msc-foot">
    <div class="org">DELEGACIÓN DIOCESANA DEL MOVIMIENTO SCOUT CATÓLICO</div>
    <div class="addr">
        C.I.F.: G-11611613, Plaza del Arroyo, 50 – 11403 Jerez de la Frontera (Cádiz).<br>
        Tfno. / Fax: 956 32 33 33 // web: www.mscjerez.es // e-mail: secretaria@mscjerez.es
    </div>
</div>
