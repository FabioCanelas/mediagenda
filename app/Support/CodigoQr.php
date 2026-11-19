<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Código QR en SVG con la librería que ya usa la verificación en dos pasos.
 */
class CodigoQr
{
    public static function svg(string $texto, int $tamano = 220): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($tamano, margin: 1), new SvgImageBackEnd)))->writeString($texto);

        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }
}
