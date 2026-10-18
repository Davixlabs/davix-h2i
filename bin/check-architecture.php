<?php
/**
 * Architecture rules for the plugin, checked like the app's architecture check:
 *
 *   1. CSS and JS files live only under assets/css and assets/js.
 *   2. Logo files live only under assets/image/logo.
 *   3. A module never uses another module (src/Modules/<A> never mentions DavixWP\Modules\<B>).
 *   4. The core (src/ outside Modules) never names a module.
 *   5. No inline <style> or <script> in PHP.
 *   6. Every operation id a module's manifest lists exists in the bundled SDK contract.
 *   7. Every module has a manifest and a <Name>Module class.
 *
 * Run: php bin/check-architecture.php   (exit code 1 on any violation)
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' ); // module manifests refuse to load outside WordPress
}
$root   = dirname( __DIR__ );
$errors = array();

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $files as $file ) {
    $path = str_replace( '\\', '/', $file->getPathname() );
    $rel  = ltrim( substr( $path, strlen( str_replace( '\\', '/', $root ) ) ), '/' );
    if ( 0 === strpos( $rel, 'vendor/' ) || 0 === strpos( $rel, 'node_modules/' ) || 0 === strpos( $rel, '.git/' ) ) {
        continue;
    }
    $ext = strtolower( pathinfo( $rel, PATHINFO_EXTENSION ) );
    if ( 'css' === $ext && 0 !== strpos( $rel, 'assets/css/' ) ) {
        $errors[] = "CSS outside assets/css: $rel";
    }
    if ( 'js' === $ext && 0 !== strpos( $rel, 'assets/js/' ) && 0 !== strpos( $rel, 'bin/' ) && 0 !== strpos( $rel, 'tests/' ) ) {
        $errors[] = "JS outside assets/js: $rel";
    }
    if ( preg_match( '/logo|favicon/i', basename( $rel ) ) && preg_match( '/\.(png|svg|jpe?g|ico|webp)$/i', $rel ) && 0 !== strpos( $rel, 'assets/image/logo/' ) ) {
        $errors[] = "Logo outside assets/image/logo: $rel";
    }
    if ( 'php' === $ext && 0 === strpos( $rel, 'src/' ) ) {
        $code = file_get_contents( $path );
        // Strings that mention the tags in prose (such as the template sanitizer) are fine; real inline blocks are not.
        if ( preg_match( '/^\s*<style\b|<\?php\s*\?>\s*<style\b|\becho\s+[\'"]\s*<style\b|\becho\s+[\'"]\s*<script\b|^\s*<script\b/mi', $code ) ) {
            $errors[] = "Inline <style> or <script> in PHP: $rel";
        }
        if ( preg_match( '#^src/Modules/([A-Za-z]+)/#', $rel, $m ) ) {
            if ( preg_match_all( '/DavixWP\\\\Modules\\\\([A-Za-z]+)\\\\/', $code, $uses ) ) {
                foreach ( array_unique( $uses[1] ) as $other ) {
                    if ( $other !== $m[1] ) {
                        $errors[] = "Module {$m[1]} uses module $other: $rel";
                    }
                }
            }
        } elseif ( 'src/Autoloader.php' !== $rel && preg_match( '/DavixWP\\\\Modules\\\\/', $code ) && 'src/Core/ModuleLoader.php' !== $rel ) {
            $errors[] = "Core names a module: $rel";
        }
    }
}

// Modules: manifest, class, operations in the contract.
$contract = json_decode( (string) file_get_contents( $root . '/vendor/davix-h2i-sdk/resources/contract.json' ), true );
$ids      = array();
foreach ( (array) ( $contract['endpoints'] ?? array() ) as $e ) {
    $ids[ $e['id'] ] = true;
}
// plugin operation => the service endpoint it calls (only where they differ)
$aliases = array( 'images.metadata-edit' => 'images.metadata' );
$seen    = array();
foreach ( glob( $root . '/src/Modules/*', GLOB_ONLYDIR ) as $dir ) {
    $name = basename( $dir );
    if ( ! is_file( "$dir/module.php" ) ) {
        $errors[] = "Module $name has no module.php";
        continue;
    }
    if ( ! is_file( "$dir/{$name}Module.php" ) ) {
        $errors[] = "Module $name has no {$name}Module.php";
    }
    $manifest = include "$dir/module.php";
    foreach ( (array) $manifest['operations'] as $op ) {
        // an operation can be a friendlier face of another endpoint (see Definition 'endpoint'): its target must be in the contract
        $target = isset( $aliases[ $op ] ) ? $aliases[ $op ] : $op;
        if ( ! isset( $ids[ $target ] ) ) {
            $errors[] = "Module $name lists $op, which is not in the SDK contract";
        }
        if ( isset( $seen[ $op ] ) ) {
            $errors[] = "Operation $op is listed by both {$seen[$op]} and $name";
        }
        $seen[ $op ] = $name;
    }
}

// The agreed 43 operations must all be owned by a module.
$agreed = array(
    'images.optimize', 'images.auto-optimize', 'images.convert', 'images.resize', 'images.transform', 'images.pipeline', 'images.watermark',
    'images.remove-background', 'images.metadata', 'images.metadata-edit', 'images.variants', 'images.inspect', 'images.compare', 'images.favicon', 'images.qr',
    'render.html-to-image', 'render.html-to-pdf', 'render.chart',
    'ai.content.alt-text', 'ai.content.caption', 'ai.content.tags', 'ai.content.seo', 'ai.images.moderate', 'ai.documents.summarize', 'ai.content.title',
    'ai.content.social-post', 'ai.content.product-description', 'ai.documents.translate', 'ai.documents.correct', 'ai.generation.generate',
    'ai.generation.background', 'ai.generation.product', 'ai.generation.social', 'ai.generation.thumbnail', 'ai.enhancement.upscale',
    'pdfs.optimize', 'pdfs.to-images', 'pdfs.merge', 'pdfs.split', 'pdfs.extract', 'pdfs.watermark', 'pdfs.security', 'documents.convert',
);
foreach ( $agreed as $op ) {
    if ( ! isset( $seen[ $op ] ) ) {
        $errors[] = "Agreed operation $op is not owned by any module";
    }
}
foreach ( $seen as $op => $mod ) {
    if ( ! in_array( $op, $agreed, true ) ) {
        $errors[] = "Operation $op ($mod) is not on the agreed list of 43";
    }
}

if ( $errors ) {
    fwrite( STDERR, "Architecture check FAILED:\n - " . implode( "\n - ", $errors ) . "\n" );
    exit( 1 );
}
echo 'Architecture check passed (' . count( $agreed ) . " operations, modules: " . count( glob( $root . '/src/Modules/*', GLOB_ONLYDIR ) ) . ").\n";
