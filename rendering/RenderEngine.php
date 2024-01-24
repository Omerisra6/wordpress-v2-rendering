<?php
namespace Rendering;

class RenderEngine {
    use ParseStyle;

    public function __construct( $elements, $styles )
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $twig = new \Twig\Environment($loader);
        $this->twig = $twig;
        $this->elements = $elements;
        $this->styles = $styles;
        $this->queries = [];
        $this->ctx = [];
    }

    public function render() {
        if ( ! isset( $this->elements ) ) {
            return '';
        }

        $root_id = 'root';
        $root_string = $this->elementToString($root_id);
        $style_string = implode( ' ', $this->renderStyle($this->styles) );

        return $style_string . $root_string;
    }

    private function elementToString($element_id) {
        $element = $this->elements[$element_id];
        $props = $element['props'];

        $transformed_props = $this->transformProps($props);

        return $this->renderElement($element['type'], $transformed_props);
    }

    private function transformProps($props) {
        $transformed_props = [];
        foreach ( $props as $prop_id => $prop ) {
            $prop_type = $prop['type'] ?? '';

            $transformed_props[$prop_id] = match ($prop_type) {
                'elements' => $this->transformElements($prop['value']),
                'styles' => $this->transformStyles($prop['value']),
                'loop' => $this->transformLoop($prop, $props),
                'products-query' => $this->transformProductsQuery(),
                'product-dynamic' => $this->transformProductDynamic($prop['value']),
                default => $prop
            };

        }
        return $transformed_props;
    }

    private function transformElements($element_ids) {
        $transformed_elements = [];
        foreach ( $element_ids as $element_id  ) {
            $transformed_elements[$element_id] = $this->elementToString($element_id);
        }
        return $transformed_elements;
    }

    private function transformStyles($style_ids) {
        $transformed_styles = [];
        foreach ( $style_ids as $style_id  ) {
            $transformed_styles[$style_id] = 'style-'.$style_id;
        }
        return $transformed_styles;
    }

    private function transformLoop($value, $transform_ctx)
    {
        $keys = $value['dependencies'];
        $items = $transform_ctx[$keys['items']];
        $template = $transform_ctx[$keys['template']];
        $empty_template = $transform_ctx[ $keys['emptyTemplate'] ];

        if ( count( $items ) === 0 ) {
            return [
                array_map( function( $id )  {
                    return $this->elementToString( $id );
                }, $empty_template )
            ];
        }

         return array_map( function( $query_ctx ) use ( $template ) {
            $this->ctx = $query_ctx;
            return $this->elementToString( $template[0] );
        }, $this->queries[ $items['type'] ] );
    }

    private function transformProductsQuery(){
        $products = file_get_contents( 'https://dummyjson.com/products/search?q=');
        $products = json_decode( $products, true );
        $this->queries['products-query'] = $products['products'];
        return $products['products'];
    }

    private function transformProductDynamic($value) {
        return $this->ctx[$value];
    }

    private function renderElement($type, $props) {
        return $this->twig->render($type.'.twig', $props);
    }

    private function renderStyle( $styles) {
        $style_elements = [];
        foreach ( $styles as $style_id => $style ) {
            $root_style_id = $this->generateStyleId( $style_id );
            $css = $this->styleToString( $styles, $style_id );

            $style_elements[] = '<style id="' . $root_style_id . '">' . $css . '</style>';
        }
        return $style_elements;
    }

    private function styleToString($styles, $style_id) {
        $style = $styles[$style_id];

        $style_string = $this->parseStyle($style);

        return $style_string;
    }
}
