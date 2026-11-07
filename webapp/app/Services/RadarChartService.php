<?php

namespace App\Services;

class RadarChartService
{
    /** @param list<float> $values
     * @param  list<int>  $standards
     */
    public function pngDataUri(array $values, array $standards): string
    {
        $image = imagecreatetruecolor(620, 460);
        $white = imagecolorallocate($image, 255, 255, 255);
        $grid = imagecolorallocate($image, 210, 218, 225);
        $text = imagecolorallocate($image, 65, 80, 95);
        $teal = imagecolorallocate($image, 13, 148, 136);
        $fill = imagecolorallocatealpha($image, 13, 148, 136, 95);
        $amber = imagecolorallocate($image, 217, 119, 6);
        imagefill($image, 0, 0, $white);
        imageantialias($image, true);
        try {
            foreach ([20, 40, 60, 80, 100] as $level) {
                imagepolygon($image, $this->points(array_fill(0, 6, $level)), $grid);
                imagestring($image, 2, 316, 220 - (int) round(1.6 * $level), (string) $level, $text);
            }
            $outer = $this->points(array_fill(0, 6, 100));
            for ($index = 0; $index < 6; $index++) {
                imageline($image, 310, 220, $outer[$index * 2], $outer[$index * 2 + 1], $grid);
            }
            $points = $this->points($values);
            imagefilledpolygon($image, $points, $fill);
            imagesetthickness($image, 2);
            imagepolygon($image, $points, $teal);
            $reference = $this->points($standards);
            imagesetstyle($image, [$amber, $amber, $amber, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT]);
            for ($index = 0; $index < 6; $index++) {
                $next = ($index + 1) % 6;
                imageline($image, $reference[$index * 2], $reference[$index * 2 + 1], $reference[$next * 2], $reference[$next * 2 + 1], IMG_COLOR_STYLED);
                imagefilledellipse($image, $points[$index * 2], $points[$index * 2 + 1], 7, 7, $teal);
            }
            $labels = $this->points(array_fill(0, 6, 117));
            for ($index = 0; $index < 6; $index++) {
                imagestring($image, 4, $labels[$index * 2] - 9, $labels[$index * 2 + 1] - 8, 'X'.($index + 1), $text);
            }
            imagestring($image, 3, 145, 430, 'Nilai peserta', $teal);
            imagestring($image, 3, 335, 430, 'Standar minimum', $amber);
            ob_start();
            try {
                imagepng($image);
                $png = ob_get_contents();
            } finally {
                ob_end_clean();
            }

            return 'data:image/png;base64,'.base64_encode($png);
        } finally {
            unset($image);
        }
    }

    /** @param list<int|float> $values
     * @return list<int>
     */
    private function points(array $values): array
    {
        $points = [];
        foreach ($values as $index => $value) {
            $angle = -M_PI / 2 + $index * M_PI / 3;
            $points[] = (int) round(310 + cos($angle) * 160 * $value / 100);
            $points[] = (int) round(220 + sin($angle) * 160 * $value / 100);
        }

        return $points;
    }
}
