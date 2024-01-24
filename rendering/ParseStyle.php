<?php
namespace Rendering;

trait ParseStyle
{
    function parseStyle($style) {
        $stylesheet = [];
        $baseSelector = $this->getBaseSelector($style);

        foreach ($style['variants'] as $variant) {
            $styleDeclaration = $this->variantToStyleDeclaration($baseSelector, $variant);

            if ($styleDeclaration) {
                $stylesheet[] = $styleDeclaration;
            }
        }

        return implode(' ', $stylesheet);
    }

    function getBaseSelector($style) {
        return $style['type'] === 'class'
            ? '.' . $this->generateStyleId($style['id'])
            : $this->generateStyleId($style['id']);
    }

    function variantToStyleDeclaration($baseSelector, $variant) {
        $state = $variant['state'] ? ':' . $variant['state'] : '';
        $selector = $baseSelector . $state;

        $css = $this->settingsToCSS($variant['settings']);

        if (!$css) {
            return '';
        }

        $css = implode('; ', $css);
        $styleDeclaration = $selector . ' { ' . $css . ' }';

        if ($variant['breakpoint']) {
            $styleDeclaration = $this->wrapWithMediaQuery($variant['breakpoint'], $styleDeclaration);
        }

        return $styleDeclaration;
    }

    function settingsToCSS($settings) {
        return array_reduce(array_keys($settings), function ($acc, $cssProp) use ($settings) {
            $acc[] = $this->camelCaseToDash($cssProp) . ': ' . $this->parseValue($settings[$cssProp]);
            return $acc;
        }, []);
    }

    function camelCaseToDash($str) {
        return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $str));
    }

    function wrapWithMediaQuery($breakpoint, $css) {
        return '@media (max-width: ' . $this->getBreakpointSize($breakpoint) . ') { ' . $css . ' }';
    }

    function parseValue($cssValue) {
        if (is_string($cssValue)) {
            return $cssValue;
        }

        return match ($cssValue['type']) {
            'global-color' => 'var(--global-color-' . $cssValue['value'] . ')',
            'global-font' => 'var(--global-font-' . $cssValue['value'] . ')',
            'dynamic' => 'unset /*' . $cssValue['value'] . '*/',
            'size' => $cssValue['value']['value'] . $cssValue['value']['unit'],
            default => $cssValue,
        };
    }

    function getBreakpointSize($breakpoint) {
        return match ($breakpoint) {
            'xs' => '576px',
            'sm' => '768px',
            'md' => '992px',
            'default' => '1024px',
            'lg' => '1200px',
            'xl' => '1400px',
            default => $breakpoint,
        };
    }

    function generateStyleId($id) {
        return 'style-' . $id;
    }

}
