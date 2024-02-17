<?php

namespace CohedaAfip\Services;

use Locale;

trait Translations
{
    public function _tr(string $string, array $params = []): string
    {
        if (function_exists('__'))
            return __($string, $params);

        if ($translation = $this->getTranslation($string))
            return $this->getReplacedTranslation($translation, $params);

        return $string;
    }

    public function _trc(string $string, int $number, array $params = []): string
    {
        if (function_exists('trans_choice'))
            return trans_choice($string, $params);

        if ($translation = $this->getTranslation($string)) {
            $translation = $this->getPluralizedTranslation($translation, $number);

            return $this->getReplacedTranslation($translation, $params);
        }

        return $string;
    }

    protected function getLocale()
    {
        $default = substr(Locale::getDefault(), 0, 5);

        return $this->env('LOCALE') ?? $default;
    }

    private function getTranslation(string $string)
    {
        $locale = $this->getLocale();
        $langFilePath = $this->path("/lang/$locale.php");

        if (file_exists($langFilePath)) {
            $langData = include $langFilePath;

            if (isset($langData[$string])) {
                return $langData[$string];
            }
        }

        return false;
    }

    private function getReplacedTranslation(string $string, array $params): string
    {
        foreach ($params as $placeholder => $value) {
            $placeholder = ':' . $placeholder;
            $string = str_replace($placeholder, $value, $string);
        }

        return $string;
    }

    private function getPluralizedTranslation(string $string, int $number): string
    {
        if (strpos($string, '|') === false) $string;

        $options = explode('|', $string);
        $singularOption = null;

        foreach ($options as $option) {
            $option = trim($option);

            if (preg_match('/^{(\d+)}(.*)/', $option, $matches))
                if ((int)$matches[1] === $number)
                    return $matches[2];

            elseif (preg_match('/^\[(\d+),(\d+)\](.*)/', $option, $matches))
                if ($number >= (int)$matches[1] && $number <= (int)$matches[2])
                    return $matches[3];

            elseif (preg_match('/^\[(\*),(\d+)\](.*)/', $option, $matches))
                if ($number <= (int)$matches[2])
                    return $matches[3];

            elseif (preg_match('/^\[(\d+),(\*)\](.*)/', $option, $matches))
                if ($number >= (int)$matches[1])
                    return $matches[3];

            elseif ($singularOption === null && $number == 1)
                return $option;

            elseif ($singularOption === null)
                $singularOption = $option;

            elseif ($singularOption !== null && $number != 1)
                return $option;
        }

        return $string;
    }
}
