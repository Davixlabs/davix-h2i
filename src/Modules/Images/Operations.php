<?php

namespace DavixWP\Modules\Images;

use DavixWP\Core\Context;
use DavixWP\Operations\Contract;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Operations {

    public static function register( Context $c ) {
        $common = array( 'group' => 'images', 'file_field' => 'images', 'accepts' => array( 'image/' ), 'release' => '2.0' );
        $out    = array( 'output_format', 'quality' );

        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.optimize',
            'label'       => __( 'Optimize', 'davix-h2i' ),
            'description' => __( 'Make files smaller without visible loss.', 'davix-h2i' ),
            'store'       => 'replace',
            'optimize'    => true,
            'fields'      => array( 'format', 'quality', 'targetSizeKB', 'maxWidth', 'maxHeight', 'colorSpace', 'keepMetadata', 'progressive', 'lossless' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.auto-optimize',
            'label'       => __( 'Smart optimize', 'davix-h2i' ),
            'description' => __( 'Picks the best settings for each image.', 'davix-h2i' ),
            'store'       => 'replace',
            'optimize'    => true,
            'fields'      => array( 'maxSizeKB', 'colorSpace', 'keepMetadata' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.convert',
            'label'       => __( 'Convert format', 'davix-h2i' ),
            'description' => __( 'WebP, AVIF, PNG, JPEG and more.', 'davix-h2i' ),
            'store'       => 'replace',
            'fields'      => array( 'output_format', 'quality', 'lossless', 'metadata', 'background' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.resize',
            'label'       => __( 'Resize', 'davix-h2i' ),
            'description' => __( 'Change the size of an image.', 'davix-h2i' ),
            'store'       => 'replace',
            'fields'      => array( 'width', 'height', 'resizeMode', 'gravity', 'enlarge', 'output_format', 'quality' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.transform',
            'label'       => __( 'Crop, rotate and effects', 'davix-h2i' ),
            'description' => __( 'Crop, rotate, flip, and adjust colors.', 'davix-h2i' ),
            'store'       => 'replace',
            'fields'      => array( 'crop.x', 'crop.y', 'crop.width', 'crop.height', 'rotate.angle', 'flip.axis', 'enhance.brightness', 'enhance.contrast', 'enhance.saturation', 'enhance.blur', 'enhance.sharpen', 'enhance.grayscale', 'enhance.sepia', 'output_format', 'quality' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'      => 'images.pipeline',
            'label'   => __( 'Several edits at once', 'davix-h2i' ),
            'store'   => 'replace',
            'hidden'  => true,
            'fields'  => array( 'operations' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.watermark',
            'label'       => __( 'Watermark', 'davix-h2i' ),
            'description' => __( 'Add your text or logo.', 'davix-h2i' ),
            'store'       => 'replace',
            'extra'       => array( 'watermarkImage' => __( 'Logo image (optional)', 'davix-h2i' ) ),
            'fields'      => array_merge( self::watermark_fields(), array( 'output_format', 'quality' ) ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.remove-background',
            'label'       => __( 'Remove background', 'davix-h2i' ),
            'description' => __( 'Remove a plain background and make it transparent.', 'davix-h2i' ),
            'store'       => 'copy',
            'fields'      => array( 'background_color', 'tolerance', 'mode', 'feather', 'shrink', 'output_format' ),
            'defaults'    => array( 'output_format' => 'png' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.metadata',
            'label'       => __( 'Read metadata', 'davix-h2i' ),
            'description' => __( 'See EXIF, size, color profile and more.', 'davix-h2i' ),
            'store'       => 'none',
            'result'      => 'json',
            'fields'      => array( 'includeRawExif', 'stripGps' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.metadata-edit',
            'label'       => __( 'Edit metadata', 'davix-h2i' ),
            'description' => __( 'Remove location data, set author and copyright.', 'davix-h2i' ),
            'store'       => 'replace',
            'fields'      => array( 'mode', 'strip_gps', 'author', 'copyright', 'description' ),
        ) ) );
        $c->add_operation( array_merge( $common, array(
            'id'          => 'images.variants',
            'label'       => __( 'Responsive sizes', 'davix-h2i' ),
            'description' => __( 'Create a set of sizes in one go.', 'davix-h2i' ),
            'store'       => 'new',
            'fields'      => array(
                array( 'name' => 'variants', 'type' => 'sizes', 'label' => __( 'Sizes', 'davix-h2i' ), 'default' => self::variants_default(), 'help' => __( 'One image is made for each row. Use 0 for the height to keep the proportions.', 'davix-h2i' ) ),
            ),
        ) ) );
        unset( $out );
    }

    /** The watermark's own options as separate fields (text, position, opacity, size of a logo ...), from the service's contract. */
    public static function watermark_fields() {
        $defaults = self::watermark_default();
        $out      = array();
        foreach ( Contract::object_fields( 'images.watermark', 'watermark' ) as $spec ) {
            if ( isset( $defaults[ $spec['key'] ] ) ) {
                $spec['default'] = $defaults[ $spec['key'] ];
            }
            $out[] = $spec;
        }
        return $out;
    }

    public static function watermark_default() {
        return array(
            'text'     => get_bloginfo( 'name' ),
            'position' => 'bottom-right',
            'opacity'  => 0.35,
            'margin'   => 24,
            'fontSize' => 24,
            'color'    => '#ffffff',
        );
    }

    /** The sizes the "Responsive sizes" action starts with; each row can be changed in its form. */
    public static function variants_default() {
        $out = array();
        foreach ( array( 'small' => 480, 'medium' => 768, 'large' => 1280, 'xlarge' => 1920 ) as $name => $width ) {
            $out[] = array( 'name' => $name, 'width' => $width, 'fit' => 'fit', 'format' => 'webp', 'quality' => 80 );
        }
        return $out;
    }
}
