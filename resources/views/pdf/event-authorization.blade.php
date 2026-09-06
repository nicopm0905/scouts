<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>AUTORIZACIÓN PARA {{ strtoupper($event->type_label ?? 'ACAMPADA') }}</title>
    <style>
        @page {
            margin: 12mm 15mm 12mm 24mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #000;
            position: relative;
        }

        /* Sidebar con el texto legal en vertical */
        .sidebar-vertical {
            position: absolute;
            left: -18mm;
            top: 240mm;
            width: 220mm;
            transform: rotate(-90deg);
            transform-origin: left top;
            font-size: 5.5pt;
            color: #222;
            line-height: 1.1;
        }

        /* Logo superior izquierdo */
        .logo-top-left {
            position: absolute;
            left: -18mm;
            top: 0;
            width: 38px;
        }

        /* Logo inferior izquierdo */
        .logo-bottom-left {
            position: absolute;
            left: -18mm;
            bottom: 5mm;
            width: 42px;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .header img.logo-center {
            height: 75px;
            margin-bottom: 4px;
        }
        .header h1 {
            font-size: 13.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .paragraph {
            margin-bottom: 10px;
            text-align: justify;
        }

        .fill-data {
            font-weight: bold;
            color: #000;
            text-decoration: underline;
        }

        .section-declaro {
            font-weight: bold;
            margin: 8px 0 4px 0;
        }

        .checkbox-block {
            margin-bottom: 10px;
            text-align: justify;
        }

        .checkbox-square {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1.4px solid #000;
            text-align: center;
            line-height: 7px;
            font-size: 8.5pt;
            font-weight: bold;
            vertical-align: middle;
            margin-right: 4px;
        }

        .lines-container {
            margin-top: 4px;
            margin-bottom: 6px;
            line-height: 1.6;
            font-weight: bold;
            font-size: 9pt;
            color: #000;
            border-bottom: 1px solid #333;
            min-height: 20px;
        }

        .signature-area {
            margin-top: 14px;
        }

        .signature-seal {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            margin-top: 4px;
            margin-bottom: 8px;
            background-color: #fafafa;
        }

        .lopd-notice {
            margin-top: 14px;
            font-size: 5.8pt;
            text-align: justify;
            color: #222;
            line-height: 1.2;
            border-top: 0.5px solid #aaa;
            padding-top: 4px;
        }

        .bottom-contact {
            margin-top: 8px;
            text-align: center;
            font-size: 7.5pt;
            font-weight: bold;
        }
    </style>
</head>
<body>

    @php
        $asidoniaPath = public_path('images/logo-asidonia-jerez.png');
        $asidoniaData = file_exists($asidoniaPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($asidoniaPath)) : null;

        $andaluciaPath = public_path('images/logo-andalucia.png');
        $andaluciaData = file_exists($andaluciaPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($andaluciaPath)) : null;

        $mscPath = public_path('images/logo-msc.png');
        $mscData = file_exists($mscPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($mscPath)) : null;
    @endphp

    <!-- Logo superior izquierdo -->
    @if($andaluciaData)
        <img src="{{ $andaluciaData }}" class="logo-top-left" alt="Scouts Católicos Andalucía" />
    @endif

    <!-- Logo inferior izquierdo -->
    @if($mscData)
        <img src="{{ $mscData }}" class="logo-bottom-left" alt="MSC" />
    @endif

    <!-- Texto lateral vertical -->
    <div class="sidebar-vertical">
        Asociación inscrita en el registro de Entidades Religiosas del Ministerio de Justicia con el nº 1898-SE/C CIF: G-11611613<br>
        Aprobado por el pleno de la Conferencia Episcopal Española el 6-VII-1973. Miembro del Movimiento Scout Católico.
    </div>

    <!-- Encabezado Central -->
    <div class="header">
        @if($asidoniaData)
            <img src="{{ $asidoniaData }}" class="logo-center" alt="Scouts Católicos Asidonia-Jerez" />
        @endif
        <h1>AUTORIZACIÓN PARA<br>{{ strtoupper($event->type_label ?? 'ACAMPADA') }}</h1>
    </div>

    <!-- Párrafo Principal de Datos -->
    <div class="paragraph">
        Yo D./DÑA.(padre/madre/tutor) <span class="fill-data">{{ $family_name ?? '___________________________________________________' }}</span><br>
        Con D.N.I <span class="fill-data">{{ $family_dni ?? '___________________' }}</span> autorizo a mi hijo/a<br>
        <span class="fill-data">{{ $member->full_name }}</span>, Nacido el día <span class="fill-data">{{ $member->birth_date ? $member->birth_date->format('d/m/Y') : '__/__/____' }}</span>, y con domicilio en<br>
        <span class="fill-data">{{ $address ?? '___________________' }}</span>, perteneciente al <strong>GRUPO SCOUT SAN JOSÉ de JEREZ DE LA FRONTERA</strong>.
    </div>

    <div class="section-declaro">Declaro:</div>

    <div class="paragraph">
        Por la presente autorización y comunico mi total acuerdo para que mi hijo/a participe en la {{ strtolower($event->type_label ?? 'acampada') }} que se celebrará en <span class="fill-data">{{ $event->location ?? '___________________' }}</span>, desde el <span class="fill-data">{{ $event->start_at->format('d') }}</span> al <span class="fill-data">{{ $event->end_at ? $event->end_at->format('d') : '__' }}</span> de <span class="fill-data">{{ $event->start_at->translatedFormat('F') }}</span> de <span class="fill-data">{{ $event->start_at->format('Y') }}</span>, y certifico que estos datos son verdaderos, de tal manera:
    </div>

    <!-- Checkbox 1: Autorización médica -->
    <div class="checkbox-block">
        <span class="checkbox-square" style="font-family: DejaVu Sans, sans-serif;">{{ ($enrollment->medical_consent ?? true) ? '✔' : '&nbsp;' }}</span> <strong>Autorizo bajo mi responsabilidad a los responsables de la Actividad</strong>, que en caso de accidente o enfermedad que requiera algún tipo de tratamiento médico o intervención quirúrgica urgente puedan tomar la decisión oportuna. Se deberá tener en cuenta las siguientes atenciones especiales (régimen alimenticio, alergias, medicación…):
        <div class="lines-container">
            {{ $health_summary }}
        </div>
    </div>

    <!-- Checkbox 2: Autorización de derechos de imagen -->
    <div class="checkbox-block">
        <span class="checkbox-square" style="font-family: DejaVu Sans, sans-serif;">{{ ($enrollment->image_consent ?? true) ? '✔' : '&nbsp;' }}</span> <strong>Autorizo al Grupo Scout San José a fijar, reproducir, comunicar y modificar (retoque fotográfico)</strong> por medio técnico las fotografías y videos realizados en el marco de la presente autorización.<br>
        En caso de no autorizar, los últimos responsables de que el autorizado no aparezca en ningún soporte audiovisual, es de sus responsables y del propio autorizado, y que estos harán en todo momento todo lo posible para que esta situación no ocurra.<br>
        Tanto las fotografías como los videos podrán reproducirse en todo soporte e integrados en cualquier otro material conocido o por conocer. Autorizo la utilización de su imagen en todos los contextos relacionados con la Federación y el Escultismo. Se entiende que la Federación prohíbe expresamente, una explotación de las fotografías susceptibles de afectar a la vida privada, y una difusión de soporte ilícito.
    </div>

    <!-- Zona de firma y fecha -->
    <div class="signature-area">
        Lo hago constar en <span class="fill-data">Jerez de la Frontera</span> a <span class="fill-data">{{ $enrollment->confirmed_at ? $enrollment->confirmed_at->format('d/m/Y') : date('d/m/Y') }}</span> &nbsp;&nbsp;&nbsp;&nbsp; <strong>Firmado:</strong>

        <div class="signature-seal">
            @if($enrollment->signature_data)
                <img src="{{ $enrollment->signature_data }}" style="max-height: 50px; max-width: 220px;" alt="Firma Manuscrita Tutor" /><br>
                <span style="color: #059669; font-weight: bold; font-size: 7.5pt;">
                    ✅ FIRMADO DIGITALMENTE VÍA WEB / WHATSAPP ({{ $enrollment->confirmed_at ? $enrollment->confirmed_at->format('d/m/Y H:i') : date('d/m/Y') }})
                </span>
            @elseif($enrollment->confirmed_at || $enrollment->hasAuthorization())
                <span style="color: #059669; font-weight: bold; font-size: 9.5pt;">✅ CONFIRMADO Y FIRMADO DIGITALMENTE VÍA WEB / WHATSAPP</span><br>
                <span style="font-size: 7pt; color: #475569;">Reg. Confirmación: #{{ $enrollment->id }}-{{ $enrollment->public_token }} ({{ $enrollment->confirmed_at ? $enrollment->confirmed_at->format('d/m/Y H:i') : date('d/m/Y') }})</span>
            @else
                <span style="color: #64748b; font-size: 8pt;">(Pendiente de firma manuscrita o digital del tutor)</span>
            @endif
        </div>

        <strong>Teléfonos de contacto en caso de urgencia:</strong> <span class="fill-data">{{ $contact_phone ?? '________________________________________' }}</span>
    </div>

    <!-- LOPD -->
    <div class="lopd-notice">
        En cumplimento de la Ley Orgánica 15/1999, de 13 de Diciembre de Protección de Datos de Carácter Personal, la Federación de Scouts Católicos de Andalucía, como responsable del fichero, informa de las siguientes consideraciones: Los datos de carácter personal que solicitamos, quedarán incorporados a un fichero cuya finalidad es gestionar las actividades y los servicios ofertados. Los campos marcados con asterisco son de cumplimentación obligatoria, siendo imposible realizar la finalidad expressed si no aporta esos datos. Queda igualmente informado de la posibilidad de ejercitar los derechos de acceso, rectificación, cancelación y oposición, mediante un escrito dirigido, y acreditando mi personalidad, a Federación de Scouts Católicos de Andalucía, Calle Limones 18, 3ª planta, 11403, Jerez de la Frontera, Cádiz.
    </div>

    <!-- Pie del Grupo -->
    <div class="bottom-contact">
        GRUPO SCOUT SAN JOSÉ · DELEGACIÓN DIOCESANA DEL MOVIMIENTO SCOUT CATÓLICO<br>
        C/ Porvera, nº 21 – 11400 Jerez de la Frontera (Cádiz). Tfno.: 676648482 Correo: sanjose@mscjerez.es
    </div>

</body>
</html>
