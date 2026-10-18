<?php
/**
 * The optimization chips (Smart, Format, Resize, ...) and how a chosen set becomes an API call: one chip uses its own
 * endpoint; two or more become a single images.pipeline call with the steps in order.
 */

namespace DavixWP\Modules\Images;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Chips {

    const FORMATS  = array( 'jpeg' => 'JPEG', 'png' => 'PNG', 'webp' => 'WebP', 'avif' => 'AVIF', 'gif' => 'GIF', 'tiff' => 'TIFF' );
    const POSITION = array( 'center' => 'Center', 'top-left' => 'Top left', 'top-right' => 'Top right', 'bottom-left' => 'Bottom left', 'bottom-right' => 'Bottom right', 'top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right' );

    /** @return array<string,array{label:string,fields:array}> */
    public static function definitions() {
        $fit = array( 'fit' => __( 'Fit inside', 'davix-h2i' ), 'cover' => __( 'Fill and crop', 'davix-h2i' ), 'pad' => __( 'Pad to exact size', 'davix-h2i' ), 'force' => __( 'Force exact size', 'davix-h2i' ) );
        return array(
            'smart'      => array(
                'label'  => __( 'Smart Optimization', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'maxSizeKB', 'label' => __( 'Largest size (KB)', 'davix-h2i' ), 'type' => 'number', 'placeholder' => '300' ),
                    array( 'name' => 'colorSpace', 'label' => __( 'Color space', 'davix-h2i' ), 'type' => 'select', 'options' => array( '' => __( 'Automatic', 'davix-h2i' ), 'srgb' => 'sRGB', 'grayscale' => __( 'Grayscale', 'davix-h2i' ) ) ),
                    array( 'name' => 'keepMetadata', 'label' => __( 'Keep metadata', 'davix-h2i' ), 'type' => 'switch' ),
                ),
            ),
            'format'     => array(
                'label'  => __( 'Format Conversion', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'format', 'label' => __( 'Format', 'davix-h2i' ), 'type' => 'select', 'options' => self::FORMATS ),
                    array( 'name' => 'quality', 'label' => __( 'Quality (1-100)', 'davix-h2i' ), 'type' => 'number', 'min' => 1, 'max' => 100, 'placeholder' => '82' ),
                ),
            ),
            'resize'     => array(
                'label'  => __( 'Resize', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'width', 'label' => __( 'Width', 'davix-h2i' ), 'type' => 'number', 'min' => 1, 'placeholder' => '1600' ),
                    array( 'name' => 'height', 'label' => __( 'Height', 'davix-h2i' ), 'type' => 'number', 'min' => 1, 'placeholder' => '' ),
                    array( 'name' => 'fit', 'label' => __( 'Resize mode', 'davix-h2i' ), 'type' => 'select', 'options' => $fit ),
                    array( 'name' => 'enlarge', 'label' => __( 'Allow enlarging', 'davix-h2i' ), 'type' => 'switch' ),
                ),
            ),
            'crop'       => array(
                'label'  => __( 'Crop', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'x', 'label' => __( 'Left (x)', 'davix-h2i' ), 'type' => 'number', 'min' => 0 ),
                    array( 'name' => 'y', 'label' => __( 'Top (y)', 'davix-h2i' ), 'type' => 'number', 'min' => 0 ),
                    array( 'name' => 'width', 'label' => __( 'Width', 'davix-h2i' ), 'type' => 'number', 'min' => 1 ),
                    array( 'name' => 'height', 'label' => __( 'Height', 'davix-h2i' ), 'type' => 'number', 'min' => 1 ),
                ),
            ),
            'transform'  => array(
                'label'  => __( 'Transform', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'angle', 'label' => __( 'Rotate (degrees)', 'davix-h2i' ), 'type' => 'number', 'placeholder' => '90' ),
                    array( 'name' => 'axis', 'label' => __( 'Flip', 'davix-h2i' ), 'type' => 'select', 'options' => array( '' => __( 'None', 'davix-h2i' ), 'horizontal' => __( 'Horizontal', 'davix-h2i' ), 'vertical' => __( 'Vertical', 'davix-h2i' ) ) ),
                ),
            ),
            'effects'    => array(
                'label'  => __( 'Effects', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'blur', 'label' => __( 'Blur', 'davix-h2i' ), 'type' => 'number', 'step' => '0.1', 'min' => 0 ),
                    array( 'name' => 'sharpen', 'label' => __( 'Sharpen', 'davix-h2i' ), 'type' => 'number', 'step' => '0.1', 'min' => 0 ),
                    array( 'name' => 'brightness', 'label' => __( 'Brightness (1 = normal)', 'davix-h2i' ), 'type' => 'number', 'step' => '0.1', 'min' => 0, 'max' => 2 ),
                    array( 'name' => 'saturation', 'label' => __( 'Saturation (1 = normal)', 'davix-h2i' ), 'type' => 'number', 'step' => '0.1', 'min' => 0, 'max' => 2 ),
                    array( 'name' => 'contrast', 'label' => __( 'Contrast (1 = normal)', 'davix-h2i' ), 'type' => 'number', 'step' => '0.1', 'min' => 0, 'max' => 2 ),
                    array( 'name' => 'grayscale', 'label' => __( 'Grayscale', 'davix-h2i' ), 'type' => 'switch' ),
                    array( 'name' => 'sepia', 'label' => __( 'Sepia', 'davix-h2i' ), 'type' => 'switch' ),
                ),
            ),
            'padding'    => array(
                'label'  => __( 'Padding', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'all', 'label' => __( 'All sides', 'davix-h2i' ), 'type' => 'number', 'min' => 0 ),
                    array( 'name' => 'color', 'label' => __( 'Color', 'davix-h2i' ), 'type' => 'text', 'placeholder' => '#ffffff' ),
                ),
            ),
            'frame'      => array(
                'label'  => __( 'Frame', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'width', 'label' => __( 'Border width', 'davix-h2i' ), 'type' => 'number', 'min' => 0 ),
                    array( 'name' => 'color', 'label' => __( 'Border color', 'davix-h2i' ), 'type' => 'text', 'placeholder' => '#000000' ),
                    array( 'name' => 'radius', 'label' => __( 'Corner radius', 'davix-h2i' ), 'type' => 'number', 'min' => 0 ),
                ),
            ),
            'background' => array(
                'label'  => __( 'Background', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'color', 'label' => __( 'Color', 'davix-h2i' ), 'type' => 'text', 'placeholder' => '#ffffff' ),
                    array( 'name' => 'blur', 'label' => __( 'Blur', 'davix-h2i' ), 'type' => 'number', 'step' => '0.1', 'min' => 0 ),
                ),
            ),
            'privacy'    => array(
                'label'  => __( 'Privacy and metadata', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'strip_gps', 'label' => __( 'Remove location (GPS)', 'davix-h2i' ), 'type' => 'switch' ),
                    array( 'name' => 'author', 'label' => __( 'Author', 'davix-h2i' ), 'type' => 'text' ),
                    array( 'name' => 'copyright', 'label' => __( 'Copyright', 'davix-h2i' ), 'type' => 'text' ),
                    array( 'name' => 'description', 'label' => __( 'Description', 'davix-h2i' ), 'type' => 'text' ),
                ),
            ),
            'watermark'  => array(
                'label'  => __( 'Watermark', 'davix-h2i' ),
                'fields' => array(
                    array( 'name' => 'text', 'label' => __( 'Text', 'davix-h2i' ), 'type' => 'text', 'placeholder' => '© My site' ),
                    array( 'name' => 'image', 'label' => __( 'Logo image (instead of or with text)', 'davix-h2i' ), 'type' => 'media' ),
                    array( 'name' => 'position', 'label' => __( 'Position', 'davix-h2i' ), 'type' => 'select', 'options' => self::POSITION ),
                    array( 'name' => 'opacity', 'label' => __( 'Opacity (0-1)', 'davix-h2i' ), 'type' => 'number', 'step' => '0.05', 'min' => 0, 'max' => 1 ),
                    array( 'name' => 'scale', 'label' => __( 'Logo size (share of the image, 0.01-1)', 'davix-h2i' ), 'type' => 'number', 'step' => '0.01', 'min' => 0.01, 'max' => 1, 'placeholder' => '0.25' ),
                    array( 'name' => 'margin', 'label' => __( 'Margin', 'davix-h2i' ), 'type' => 'number', 'min' => 0 ),
                    array( 'name' => 'fontSize', 'label' => __( 'Text size', 'davix-h2i' ), 'type' => 'number', 'min' => 6, 'max' => 400 ),
                    array( 'name' => 'color', 'label' => __( 'Text color', 'davix-h2i' ), 'type' => 'text', 'placeholder' => '#ffffff' ),
                    array( 'name' => 'rotation', 'label' => __( 'Rotate (degrees)', 'davix-h2i' ), 'type' => 'number', 'step' => '1' ),
                    array( 'name' => 'tile', 'label' => __( 'Repeat across the whole image', 'davix-h2i' ), 'type' => 'switch' ),
                    array( 'name' => 'tileSpacing', 'label' => __( 'Space between repeats', 'davix-h2i' ), 'type' => 'number', 'min' => 0 ),
                    array( 'name' => 'blendMode', 'label' => __( 'Blend mode', 'davix-h2i' ), 'type' => 'select', 'options' => self::blend_modes() ),
                    array( 'name' => 'fontWeight', 'label' => __( 'Text weight', 'davix-h2i' ), 'type' => 'text', 'placeholder' => 'bold' ),
                    array( 'name' => 'italic', 'label' => __( 'Italic text', 'davix-h2i' ), 'type' => 'switch' ),
                    array( 'name' => 'letterSpacing', 'label' => __( 'Letter spacing', 'davix-h2i' ), 'type' => 'number', 'step' => '0.5' ),
                ),
            ),
        );
    }

    /** The blend modes the service offers for a watermark, read from its contract. */
    public static function blend_modes() {
        $out = array( '' => __( 'Normal', 'davix-h2i' ) );
        foreach ( \DavixWP\Operations\Contract::object_fields( 'images.watermark', 'watermark' ) as $spec ) {
            if ( 'blendMode' === $spec['key'] && ! empty( $spec['options'] ) ) {
                foreach ( $spec['options'] as $v ) {
                    $out[ $v ] = ucfirst( str_replace( '-', ' ', $v ) );
                }
                unset( $out['over'] );
            }
        }
        return $out;
    }

    public static function labels() {
        $out = array();
        foreach ( self::definitions() as $key => $def ) {
            $out[ $key ] = $def['label'];
        }
        return $out;
    }

    /** Which chips only ever make sense as the final encoding step. */
    private static function is_encoding( $chip ) {
        return in_array( $chip, array( 'smart', 'format' ), true );
    }

    /**
     * @param string[] $chips  chosen chips in order
     * @param array    $fields fields by chip: array( 'resize' => array( 'width' => 1200 ) )
     * @return array|null array( 'operation' => id, 'params' => array ) or null when nothing is chosen
     */
    public static function build( array $chips, array $fields ) {
        $chips = array_values( array_unique( array_filter( $chips, 'is_string' ) ) );
        $defs  = self::definitions();
        $chips = array_values( array_filter( $chips, function ( $c ) use ( $defs ) {
            return isset( $defs[ $c ] );
        } ) );
        if ( ! $chips ) {
            return null;
        }
        $get = function ( $chip ) use ( $fields ) {
            return isset( $fields[ $chip ] ) && is_array( $fields[ $chip ] ) ? self::clean( $fields[ $chip ] ) : array();
        };

        // Hidden information is not a pixel step: it runs as a second call on the result of the others (or alone).
        if ( in_array( 'privacy', $chips, true ) ) {
            $meta  = array( 'operation' => 'images.metadata-edit', 'params' => $get( 'privacy' ) );
            $rest  = array_values( array_diff( $chips, array( 'privacy' ) ) );
            if ( ! $rest ) {
                return $meta;
            }
            $plan = self::build( $rest, $fields );
            if ( $plan ) {
                $plan['then'] = $meta;
            }
            return $plan;
        }

        if ( 1 === count( $chips ) ) {
            $chip = $chips[0];
            $f    = $get( $chip );
            switch ( $chip ) {
                case 'smart':
                    return array( 'operation' => 'images.auto-optimize', 'params' => $f );
                case 'format':
                    $p = array( 'output_format' => isset( $f['format'] ) ? $f['format'] : 'webp' );
                    if ( isset( $f['quality'] ) ) {
                        $p['quality'] = $f['quality'];
                    }
                    return array( 'operation' => 'images.convert', 'params' => $p );
                case 'resize':
                    $p = array();
                    foreach ( array( 'width', 'height', 'enlarge' ) as $k ) {
                        if ( isset( $f[ $k ] ) ) {
                            $p[ $k ] = $f[ $k ];
                        }
                    }
                    if ( isset( $f['fit'] ) ) {
                        $p['resizeMode'] = $f['fit'];
                    }
                    return array( 'operation' => 'images.resize', 'params' => $p );
                case 'crop':
                    $p = array();
                    foreach ( array( 'x', 'y', 'width', 'height' ) as $k ) {
                        if ( isset( $f[ $k ] ) ) {
                            $p[ 'crop.' . $k ] = $f[ $k ];
                        }
                    }
                    return array( 'operation' => 'images.transform', 'params' => $p );
                case 'transform':
                    $p = array();
                    if ( isset( $f['angle'] ) ) {
                        $p['rotate.angle'] = $f['angle'];
                    }
                    if ( isset( $f['axis'] ) ) {
                        $p['flip.axis'] = $f['axis'];
                    }
                    return array( 'operation' => 'images.transform', 'params' => $p );
                case 'effects':
                    $p = array();
                    foreach ( $f as $k => $v ) {
                        $p[ 'enhance.' . $k ] = $v;
                    }
                    return array( 'operation' => 'images.transform', 'params' => $p );
                case 'watermark':
                    return self::with_logo( array( 'operation' => 'images.watermark', 'params' => array( 'watermark' => self::without_logo( $f ) ) ), $f );
                default:
                    // padding, frame, background have no dedicated endpoint: a one-step pipeline.
                    break;
            }
        }

        // Several chips (or one without its own endpoint): one pipeline call, encoding steps last.
        usort(
            $chips,
            function ( $a, $b ) {
                return (int) self::is_encoding( $a ) - (int) self::is_encoding( $b );
            }
        );
        $steps = array();
        foreach ( $chips as $chip ) {
            foreach ( self::steps( $chip, $get( $chip ) ) as $step ) {
                $steps[] = $step;
            }
        }
        if ( ! $steps ) {
            return null;
        }
        $plan = array( 'operation' => 'images.pipeline', 'params' => array( 'operations' => $steps ) );
        return self::with_logo( $plan, $get( 'watermark' ) );
    }

    /** The chosen logo (a Media Library file) goes out as the file "watermarkImage"; it is not part of the watermark settings. */
    private static function with_logo( array $plan, array $f ) {
        if ( ! empty( $f['image'] ) ) {
            $plan['params']['_extra'] = array( 'watermarkImage' => (int) $f['image'] );
        }
        return $plan;
    }

    private static function without_logo( array $f ) {
        unset( $f['image'] );
        return $f;
    }

    private static function steps( $chip, array $f ) {
        switch ( $chip ) {
            case 'privacy':
                return array();
            case 'smart':
                $s = array( 'op' => 'optimize' );
                if ( isset( $f['maxSizeKB'] ) ) {
                    $s['targetSizeKB'] = $f['maxSizeKB'];
                }
                foreach ( array( 'colorSpace', 'keepMetadata' ) as $k ) {
                    if ( isset( $f[ $k ] ) ) {
                        $s[ $k ] = $f[ $k ];
                    }
                }
                return array( $s );
            case 'format':
                $s = array( 'op' => 'optimize' );
                foreach ( array( 'format', 'quality' ) as $k ) {
                    if ( isset( $f[ $k ] ) ) {
                        $s[ $k ] = $f[ $k ];
                    }
                }
                return array( $s );
            case 'resize':
                return array( array_merge( array( 'op' => 'resize' ), $f ) );
            case 'crop':
                return array( array_merge( array( 'op' => 'crop' ), $f ) );
            case 'transform':
                $out = array();
                if ( isset( $f['angle'] ) ) {
                    $out[] = array( 'op' => 'rotate', 'angle' => $f['angle'] );
                }
                if ( isset( $f['axis'] ) ) {
                    $out[] = array( 'op' => 'flip', 'axis' => $f['axis'] );
                }
                return $out;
            case 'effects':
                return $f ? array( array_merge( array( 'op' => 'enhance' ), $f ) ) : array();
            case 'padding':
                return array( array_merge( array( 'op' => 'padding' ), $f ) );
            case 'frame':
                return array( array_merge( array( 'op' => 'border' ), $f ) );
            case 'background':
                return array( array_merge( array( 'op' => 'background' ), $f ) );
            case 'watermark':
                return array( array_merge( array( 'op' => 'watermark' ), self::without_logo( $f ) ) );
        }
        return array();
    }

    /** Drops empty values and turns numeric strings and "1"/"0" into numbers and booleans. */
    private static function clean( array $fields ) {
        $out   = array();
        $bools = array( 'strip_gps', 'keepMetadata', 'enlarge', 'grayscale', 'sepia', 'tile', 'italic' );
        foreach ( $fields as $k => $v ) {
            if ( '' === $v || null === $v ) {
                continue;
            }
            if ( in_array( $k, $bools, true ) ) {
                if ( $v && '0' !== $v ) {
                    $out[ $k ] = true;
                }
                continue;
            }
            if ( is_string( $v ) && is_numeric( $v ) ) {
                $v = $v + 0;
            }
            $out[ $k ] = $v;
        }
        return $out;
    }
}
