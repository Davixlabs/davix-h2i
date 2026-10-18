<?php
/**
 * Facts about attachments the operations need: path, type, size, and which operations fit a file.
 */

namespace DavixWP\Media;

use DavixWP\Operations\Definition;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Selector {

    /**
     * @return array{id:int,path:string,filename:string,mime:string,size:int,title:string,exists:bool}|null
     */
    public static function info( $attachment_id ) {
        $post = get_post( (int) $attachment_id );
        if ( ! $post || 'attachment' !== $post->post_type ) {
            return null;
        }
        $path = get_attached_file( $post->ID );
        $has  = $path && is_file( $path );
        return array(
            'id'       => (int) $post->ID,
            'path'     => (string) $path,
            'filename' => $path ? wp_basename( $path ) : '',
            'mime'     => (string) $post->post_mime_type,
            'size'     => $has ? (int) filesize( $path ) : 0,
            'title'    => get_the_title( $post ) ? get_the_title( $post ) : wp_basename( (string) $path ),
            'exists'   => (bool) $has,
        );
    }

    /** Is the file a kind of document the Documents operations take (Office, PDF, text, RTF)? */
    public static function is_document( $mime ) {
        return 0 === strpos( (string) $mime, 'application/vnd.' ) || in_array( (string) $mime, array( 'application/msword', 'application/rtf', 'text/rtf', 'text/plain', 'text/csv' ), true );
    }

    /**
     * Operation ids from a list that accept the given attachment.
     *
     * @param array[] $definitions operation definitions
     */
    public static function operations_for( $attachment_id, array $definitions ) {
        $info = self::info( $attachment_id );
        if ( ! $info ) {
            return array();
        }
        $out = array();
        foreach ( $definitions as $def ) {
            if ( Definition::accepts( $def, $info['mime'] ) ) {
                $out[] = $def['id'];
            }
        }
        return $out;
    }
}
