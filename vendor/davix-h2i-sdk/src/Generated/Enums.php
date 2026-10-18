<?php
// GENERATED from the Davix H2I API contract 3.0.0. Do not edit.
// $x = \Davix\H2I\Enums\ImageFormat::WEBP;  \Davix\H2I\Enums\ImageFormat::values() === ["jpeg", ...]
namespace Davix\H2I\Enums;

final class AiAnswerStyle
{
    const CONCISE = 'concise';
    const DETAILED = 'detailed';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['concise', 'detailed'];
    }
}

final class AiAspectRatio
{
    const V_1_1 = '1:1';
    const V_16_9 = '16:9';
    const V_9_16 = '9:16';
    const V_4_3 = '4:3';
    const V_3_4 = '3:4';
    const V_3_2 = '3:2';
    const V_2_3 = '2:3';
    const V_21_9 = '21:9';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['1:1', '16:9', '9:16', '4:3', '3:4', '3:2', '2:3', '21:9'];
    }
}

final class AiBackground
{
    const STUDIO_WHITE = 'studio-white';
    const GRADIENT = 'gradient';
    const LIFESTYLE = 'lifestyle';
    const CUSTOM = 'custom';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['studio-white', 'gradient', 'lifestyle', 'custom'];
    }
}

final class AiCategoriesItem
{
    const SEXUAL = 'sexual';
    const NUDITY = 'nudity';
    const VIOLENCE = 'violence';
    const GRAPHIC_INJURY = 'graphic_injury';
    const HATE_SYMBOLS = 'hate_symbols';
    const SELF_HARM = 'self_harm';
    const WEAPONS = 'weapons';
    const DRUGS = 'drugs';
    const MINORS_RISK = 'minors_risk';
    const SPAM_OR_SCAM = 'spam_or_scam';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['sexual', 'nudity', 'violence', 'graphic_injury', 'hate_symbols', 'self_harm', 'weapons', 'drugs', 'minors_risk', 'spam_or_scam'];
    }
}

final class AiContentSocialPostPlatform
{
    const TWITTER = 'twitter';
    const LINKEDIN = 'linkedin';
    const FACEBOOK = 'facebook';
    const INSTAGRAM = 'instagram';
    const THREADS = 'threads';
    const GENERIC = 'generic';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['twitter', 'linkedin', 'facebook', 'instagram', 'threads', 'generic'];
    }
}

final class AiDetail
{
    const BRIEF = 'brief';
    const STANDARD = 'standard';
    const DETAILED = 'detailed';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['brief', 'standard', 'detailed'];
    }
}

final class AiDocumentsRewriteStyle
{
    const FORMAL = 'formal';
    const CASUAL = 'casual';
    const CONCISE = 'concise';
    const FRIENDLY = 'friendly';
    const ACADEMIC = 'academic';
    const MARKETING = 'marketing';
    const SIMPLIFIED = 'simplified';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['formal', 'casual', 'concise', 'friendly', 'academic', 'marketing', 'simplified'];
    }
}

final class AiDocumentsSummarizeStyle
{
    const PARAGRAPH = 'paragraph';
    const BULLETS = 'bullets';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['paragraph', 'bullets'];
    }
}

final class AiGenerationEditMode
{
    const EDIT = 'edit';
    const REPLACE_BACKGROUND = 'replace-background';
    const REMOVE_OBJECT = 'remove-object';
    const REPLACE_OBJECT = 'replace-object';
    const GENERATIVE_FILL = 'generative-fill';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['edit', 'replace-background', 'remove-object', 'replace-object', 'generative-fill'];
    }
}

final class AiGenerationPosterSize
{
    const A4 = 'a4';
    const A3 = 'a3';
    const LETTER = 'letter';
    const TABLOID = 'tabloid';
    const CUSTOM = 'custom';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['a4', 'a3', 'letter', 'tabloid', 'custom'];
    }
}

final class AiGenerationRemoveBackgroundOutput
{
    const CUTOUT = 'cutout';
    const MASK = 'mask';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['cutout', 'mask'];
    }
}

final class AiGenerationSocialPlatform
{
    const INSTAGRAM_SQUARE = 'instagram-square';
    const INSTAGRAM_STORY = 'instagram-story';
    const TWITTER = 'twitter';
    const FACEBOOK = 'facebook';
    const LINKEDIN = 'linkedin';
    const PINTEREST = 'pinterest';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['instagram-square', 'instagram-story', 'twitter', 'facebook', 'linkedin', 'pinterest'];
    }
}

final class AiGenerationThumbnailPlatform
{
    const YOUTUBE = 'youtube';
    const BLOG = 'blog';
    const GENERIC = 'generic';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['youtube', 'blog', 'generic'];
    }
}

final class AiGrounding
{
    const STRICT = 'strict';
    const FLAG = 'flag';
    const OFF = 'off';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['strict', 'flag', 'off'];
    }
}

final class AiImagesCaptionTone
{
    const NEUTRAL = 'neutral';
    const FRIENDLY = 'friendly';
    const PROFESSIONAL = 'professional';
    const PLAYFUL = 'playful';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['neutral', 'friendly', 'professional', 'playful'];
    }
}

final class AiInclude
{
    const ENDPOINTS = 'endpoints';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['endpoints'];
    }
}

final class AiIncludeItem
{
    const ENTITIES = 'entities';
    const DATES = 'dates';
    const AMOUNTS = 'amounts';
    const ADDRESSES = 'addresses';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['entities', 'dates', 'amounts', 'addresses'];
    }
}

final class AiLength
{
    const SHORT = 'short';
    const MEDIUM = 'medium';
    const LONG = 'long';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['short', 'medium', 'long'];
    }
}

final class AiMode
{
    const GRAMMAR = 'grammar';
    const SPELLING = 'spelling';
    const BOTH = 'both';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['grammar', 'spelling', 'both'];
    }
}

final class AiOnUnsupported
{
    const ERROR = 'error';
    const IGNORE = 'ignore';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['error', 'ignore'];
    }
}

final class AiOutput
{
    const SEPARATE = 'separate';
    const COMBINED = 'combined';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['separate', 'combined'];
    }
}

final class AiOutputFormat
{
    const PNG = 'png';
    const JPEG = 'jpeg';
    const WEBP = 'webp';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['png', 'jpeg', 'webp'];
    }
}

final class AiOutputModality
{
    const TEXT = 'text';
    const IMAGE = 'image';
    const AUDIO = 'audio';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['text', 'image', 'audio'];
    }
}

final class AiQuality
{
    const STANDARD = 'standard';
    const HIGH = 'high';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['standard', 'high'];
    }
}

final class AiRole
{
    const SYSTEM = 'system';
    const USER = 'user';
    const ASSISTANT = 'assistant';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['system', 'user', 'assistant'];
    }
}

final class AiScale
{
    const V_2 = 2;
    const V_4 = 4;

    /** @return array<int,string|int> */
    public static function values()
    {
        return [2, 4];
    }
}

final class AiSize
{
    const V_512 = '512';
    const V_1K = '1K';
    const V_2K = '2K';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['512', '1K', '2K'];
    }
}

final class AiStrategy
{
    const AUTO = 'auto';
    const LOCAL = 'local';
    const VISION = 'vision';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['auto', 'local', 'vision'];
    }
}

final class AiStrength
{
    const LOW = 'low';
    const MEDIUM = 'medium';
    const HIGH = 'high';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['low', 'medium', 'high'];
    }
}

final class AiStrictness
{
    const LOW = 'low';
    const MEDIUM = 'medium';
    const HIGH = 'high';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['low', 'medium', 'high'];
    }
}

final class AiTargetAspectRatio
{
    const V_1_1 = '1:1';
    const V_16_9 = '16:9';
    const V_9_16 = '9:16';
    const V_4_3 = '4:3';
    const V_3_4 = '3:4';
    const V_3_2 = '3:2';
    const V_2_3 = '2:3';
    const V_21_9 = '21:9';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['1:1', '16:9', '9:16', '4:3', '3:4', '3:2', '2:3', '21:9'];
    }
}

final class AiTargetLength
{
    const SHORTER = 'shorter';
    const SAME = 'same';
    const LONGER = 'longer';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['shorter', 'same', 'longer'];
    }
}

final class AiTextPosition
{
    const TOP = 'top';
    const CENTER = 'center';
    const BOTTOM = 'bottom';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['top', 'center', 'bottom'];
    }
}

final class AiTone
{
    const NEUTRAL = 'neutral';
    const FRIENDLY = 'friendly';
    const PROFESSIONAL = 'professional';
    const PLAYFUL = 'playful';
    const PERSUASIVE = 'persuasive';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['neutral', 'friendly', 'professional', 'playful', 'persuasive'];
    }
}

final class AiType
{
    const STRING = 'string';
    const NUMBER = 'number';
    const DATE = 'date';
    const CURRENCY = 'currency';
    const BOOLEAN = 'boolean';
    const LIST = 'list';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['string', 'number', 'date', 'currency', 'boolean', 'list'];
    }
}

final class Align
{
    const LEFT = 'left';
    const CENTER = 'center';
    const RIGHT = 'right';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['left', 'center', 'right'];
    }
}

final class ColorSpace
{
    const SRGB = 'srgb';
    const GRAYSCALE = 'grayscale';
    const CMYK = 'cmyk';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['srgb', 'grayscale', 'cmyk'];
    }
}

final class DataJoinType
{
    const INNER = 'inner';
    const LEFT = 'left';
    const RIGHT = 'right';
    const OUTER = 'outer';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['inner', 'left', 'right', 'outer'];
    }
}

final class DataMode
{
    const APPEND = 'append';
    const JOIN = 'join';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['append', 'join'];
    }
}

final class DataOp
{
    const SELECT = 'select';
    const DROP = 'drop';
    const RENAME = 'rename';
    const FILTER = 'filter';
    const SORT = 'sort';
    const LIMIT = 'limit';
    const OFFSET = 'offset';
    const SET = 'set';
    const CAST = 'cast';
    const DEDUPE = 'dedupe';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['select', 'drop', 'rename', 'filter', 'sort', 'limit', 'offset', 'set', 'cast', 'dedupe'];
    }
}

final class DataTarget
{
    const AUTO = 'auto';
    const ROWS = 'rows';
    const DOCUMENT = 'document';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['auto', 'rows', 'document'];
    }
}

final class DataTo
{
    const XLSX = 'xlsx';
    const ODS = 'ods';
    const MD = 'md';
    const HTML = 'html';
    const XLS = 'xls';
    const CSV = 'csv';
    const JSON = 'json';
    const XML = 'xml';
    const TSV = 'tsv';
    const NDJSON = 'ndjson';
    const YAML = 'yaml';
    const TOML = 'toml';
    const SQL = 'sql';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['xlsx', 'ods', 'md', 'html', 'xls', 'csv', 'json', 'xml', 'tsv', 'ndjson', 'yaml', 'toml', 'sql'];
    }
}

final class Direction
{
    const AUTO = 'auto';
    const LTR = 'ltr';
    const RTL = 'rtl';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['auto', 'ltr', 'rtl'];
    }
}

final class DocumentsConvertTo
{
    const JPEG = 'jpeg';
    const PNG = 'png';
    const SVG = 'svg';
    const PDF = 'pdf';
    const PDFA = 'pdfa';
    const DOCX = 'docx';
    const XLSX = 'xlsx';
    const PPTX = 'pptx';
    const ODT = 'odt';
    const ODS = 'ods';
    const ODP = 'odp';
    const RTF = 'rtf';
    const EPUB = 'epub';
    const TXT = 'txt';
    const MD = 'md';
    const HTML = 'html';
    const DOC = 'doc';
    const XLS = 'xls';
    const PPT = 'ppt';
    const PPS = 'pps';
    const PPSX = 'ppsx';
    const CSV = 'csv';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['jpeg', 'png', 'svg', 'pdf', 'pdfa', 'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'rtf', 'epub', 'txt', 'md', 'html', 'doc', 'xls', 'ppt', 'pps', 'ppsx', 'csv'];
    }
}

final class DocumentsMailMergeTo
{
    const DOCX = 'docx';
    const ODT = 'odt';
    const PDF = 'pdf';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['docx', 'odt', 'pdf'];
    }
}

final class DocumentsOutput
{
    const INLINE = 'inline';
    const URL = 'url';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['inline', 'url'];
    }
}

final class DocumentsPdfaLevel
{
    const V_1B = '1b';
    const V_2B = '2b';
    const V_3B = '3b';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['1b', '2b', '3b'];
    }
}

final class DocumentsPreviewFormat
{
    const PNG = 'png';
    const JPEG = 'jpeg';
    const WEBP = 'webp';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['png', 'jpeg', 'webp'];
    }
}

final class DocumentsTextFormat
{
    const TXT = 'txt';
    const MD = 'md';
    const JSON = 'json';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['txt', 'md', 'json'];
    }
}

final class FilesCategory
{
    const IMAGE = 'image';
    const PDF = 'pdf';
    const RENDER = 'render';
    const OTHER = 'other';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['image', 'pdf', 'render', 'other'];
    }
}

final class FilesSort
{
    const CREATED_AT_DESC = 'created_at_desc';
    const CREATED_AT_ASC = 'created_at_asc';
    const SIZE_DESC = 'size_desc';
    const SIZE_ASC = 'size_asc';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['created_at_desc', 'created_at_asc', 'size_desc', 'size_asc'];
    }
}

final class FilesStatus
{
    const AVAILABLE = 'available';
    const EXPIRED = 'expired';
    const ALL = 'all';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['available', 'expired', 'all'];
    }
}

final class FlipAxis
{
    const HORIZONTAL = 'horizontal';
    const VERTICAL = 'vertical';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['horizontal', 'vertical'];
    }
}

final class ImageFormat
{
    const JPEG = 'jpeg';
    const PNG = 'png';
    const WEBP = 'webp';
    const AVIF = 'avif';
    const GIF = 'gif';
    const TIFF = 'tiff';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['jpeg', 'png', 'webp', 'avif', 'gif', 'tiff'];
    }
}

final class ImageGravity
{
    const CENTER = 'center';
    const NORTH = 'north';
    const NORTHEAST = 'northeast';
    const EAST = 'east';
    const SOUTHEAST = 'southeast';
    const SOUTH = 'south';
    const SOUTHWEST = 'southwest';
    const WEST = 'west';
    const NORTHWEST = 'northwest';
    const ENTROPY = 'entropy';
    const ATTENTION = 'attention';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['center', 'north', 'northeast', 'east', 'southeast', 'south', 'southwest', 'west', 'northwest', 'entropy', 'attention'];
    }
}

final class ImagesBlendFit
{
    const COVER = 'cover';
    const CONTAIN = 'contain';
    const FILL = 'fill';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['cover', 'contain', 'fill'];
    }
}

final class ImagesBlendGravity
{
    const CENTER = 'center';
    const NORTH = 'north';
    const NORTHEAST = 'northeast';
    const EAST = 'east';
    const SOUTHEAST = 'southeast';
    const SOUTH = 'south';
    const SOUTHWEST = 'southwest';
    const WEST = 'west';
    const NORTHWEST = 'northwest';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['center', 'north', 'northeast', 'east', 'southeast', 'south', 'southwest', 'west', 'northwest'];
    }
}

final class ImagesBlendMode
{
    const CLEAR = 'clear';
    const SOURCE = 'source';
    const OVER = 'over';
    const IN = 'in';
    const OUT = 'out';
    const ATOP = 'atop';
    const DEST = 'dest';
    const DEST_OVER = 'dest-over';
    const DEST_IN = 'dest-in';
    const DEST_OUT = 'dest-out';
    const DEST_ATOP = 'dest-atop';
    const XOR = 'xor';
    const ADD = 'add';
    const SATURATE = 'saturate';
    const MULTIPLY = 'multiply';
    const SCREEN = 'screen';
    const OVERLAY = 'overlay';
    const DARKEN = 'darken';
    const LIGHTEN = 'lighten';
    const COLOR_DODGE = 'color-dodge';
    const COLOR_BURN = 'color-burn';
    const HARD_LIGHT = 'hard-light';
    const SOFT_LIGHT = 'soft-light';
    const DIFFERENCE = 'difference';
    const EXCLUSION = 'exclusion';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['clear', 'source', 'over', 'in', 'out', 'atop', 'dest', 'dest-over', 'dest-in', 'dest-out', 'dest-atop', 'xor', 'add', 'saturate', 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'color-burn', 'hard-light', 'soft-light', 'difference', 'exclusion'];
    }
}

final class ImagesChannelExtract
{
    const RED = 'red';
    const GREEN = 'green';
    const BLUE = 'blue';
    const ALPHA = 'alpha';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['red', 'green', 'blue', 'alpha'];
    }
}

final class ImagesChecksItem
{
    const COLORS = 'colors';
    const PALETTE = 'palette';
    const HASH = 'hash';
    const SIMILARITY = 'similarity';
    const TRANSPARENCY = 'transparency';
    const QUALITY = 'quality';
    const EFFICIENCY = 'efficiency';
    const DIMENSIONS = 'dimensions';
    const ASPECTRATIO = 'aspectratio';
    const FORMAT = 'format';
    const ANIMATION = 'animation';
    const METADATASUMMARY = 'metadatasummary';
    const OPTIMIZATION = 'optimization';
    const STATS = 'stats';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['colors', 'palette', 'hash', 'similarity', 'transparency', 'quality', 'efficiency', 'dimensions', 'aspectratio', 'format', 'animation', 'metadatasummary', 'optimization', 'stats'];
    }
}

final class ImagesChromaSubsampling
{
    const V_4_4_4 = '4:4:4';
    const V_4_2_0 = '4:2:0';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['4:4:4', '4:2:0'];
    }
}

final class ImagesColorspace
{
    const SRGB = 'srgb';
    const GRAYSCALE = 'grayscale';
    const CMYK = 'cmyk';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['srgb', 'grayscale', 'cmyk'];
    }
}

final class ImagesDisplay
{
    const STANDALONE = 'standalone';
    const MINIMAL_UI = 'minimal-ui';
    const FULLSCREEN = 'fullscreen';
    const BROWSER = 'browser';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['standalone', 'minimal-ui', 'fullscreen', 'browser'];
    }
}

final class ImagesEmbedFormat
{
    const JPEG = 'jpeg';
    const JPG = 'jpg';
    const PNG = 'png';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['jpeg', 'jpg', 'png'];
    }
}

final class ImagesErrorCorrection
{
    const L = 'L';
    const M = 'M';
    const Q = 'Q';
    const H = 'H';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['L', 'M', 'Q', 'H'];
    }
}

final class ImagesFileType
{
    const PNG = 'png';
    const SVG = 'svg';
    const PDF = 'pdf';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['png', 'svg', 'pdf'];
    }
}

final class ImagesFormat
{
    const GIF = 'gif';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['gif'];
    }
}

final class ImagesGradient
{
    const LINEAR = 'linear';
    const RADIAL = 'radial';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['linear', 'radial'];
    }
}

final class ImagesHashType
{
    const PHASH = 'phash';
    const MD5 = 'md5';
    const SHA1 = 'sha1';
    const SHA256 = 'sha256';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['phash', 'md5', 'sha1', 'sha256'];
    }
}

final class ImagesIccProfile
{
    const SRGB = 'srgb';
    const P3 = 'p3';
    const CMYK = 'cmyk';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['srgb', 'p3', 'cmyk'];
    }
}

final class ImagesInterpolator
{
    const NEAREST = 'nearest';
    const BILINEAR = 'bilinear';
    const BICUBIC = 'bicubic';
    const LOCALLYBOUNDEDBICUBIC = 'locallyBoundedBicubic';
    const NOHALO = 'nohalo';
    const VERTEXSPLITQUADRATICBASISSPLINE = 'vertexSplitQuadraticBasisSpline';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['nearest', 'bilinear', 'bicubic', 'locallyBoundedBicubic', 'nohalo', 'vertexSplitQuadraticBasisSpline'];
    }
}

final class ImagesKernel
{
    const NEAREST = 'nearest';
    const LINEAR = 'linear';
    const CUBIC = 'cubic';
    const MITCHELL = 'mitchell';
    const LANCZOS2 = 'lanczos2';
    const LANCZOS3 = 'lanczos3';
    const MKS2013 = 'mks2013';
    const MKS2021 = 'mks2021';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['nearest', 'linear', 'cubic', 'mitchell', 'lanczos2', 'lanczos3', 'mks2013', 'mks2021'];
    }
}

final class ImagesLabels
{
    const NONE = 'none';
    const FILENAME = 'filename';
    const INDEX = 'index';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['none', 'filename', 'index'];
    }
}

final class ImagesMetadata
{
    const STRIP = 'strip';
    const KEEP = 'keep';
    const SET = 'set';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['strip', 'keep', 'set'];
    }
}

final class ImagesMetadataEditMode
{
    const MERGE = 'merge';
    const REPLACE = 'replace';
    const STRIP = 'strip';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['merge', 'replace', 'strip'];
    }
}

final class ImagesOp
{
    const AUTOORIENT = 'autoOrient';
    const CROP = 'crop';
    const RESIZE = 'resize';
    const ROTATE = 'rotate';
    const FLIP = 'flip';
    const ENHANCE = 'enhance';
    const BACKGROUND = 'background';
    const PADDING = 'padding';
    const BORDER = 'border';
    const WATERMARK = 'watermark';
    const OPTIMIZE = 'optimize';
    const TRIM = 'trim';
    const COLORSPACE = 'colorSpace';
    const CONVOLVE = 'convolve';
    const AFFINE = 'affine';
    const CHANNELEXTRACT = 'channelExtract';
    const NOISE = 'noise';
    const VIGNETTE = 'vignette';
    const PIXELATE = 'pixelate';
    const POSTERIZE = 'posterize';
    const DUOTONE = 'duotone';
    const EMBOSS = 'emboss';
    const EDGEDETECT = 'edgeDetect';
    const TILE = 'tile';
    const SLICE = 'slice';
    const GRID = 'grid';
    const CONTACTSHEET = 'contactSheet';
    const SPRITESHEET = 'spriteSheet';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['autoOrient', 'crop', 'resize', 'rotate', 'flip', 'enhance', 'background', 'padding', 'border', 'watermark', 'optimize', 'trim', 'colorSpace', 'convolve', 'affine', 'channelExtract', 'noise', 'vignette', 'pixelate', 'posterize', 'duotone', 'emboss', 'edgeDetect', 'tile', 'slice', 'grid', 'contactSheet', 'spriteSheet'];
    }
}

final class ImagesOutput
{
    const TEXT = 'text';
    const PDF = 'pdf';
    const JSON = 'json';
    const TSV = 'tsv';
    const HOCR = 'hocr';
    const ALTO = 'alto';
    const CSV = 'csv';
    const XLSX = 'xlsx';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['text', 'pdf', 'json', 'tsv', 'hocr', 'alto', 'csv', 'xlsx'];
    }
}

final class ImagesOutputFormat
{
    const JPEG = 'jpeg';
    const PNG = 'png';
    const WEBP = 'webp';
    const GIF = 'gif';
    const AVIF = 'avif';
    const TIFF = 'tiff';
    const BMP = 'bmp';
    const ICO = 'ico';
    const APNG = 'apng';
    const JXL = 'jxl';
    const JP2 = 'jp2';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['jpeg', 'png', 'webp', 'gif', 'avif', 'tiff', 'bmp', 'ico', 'apng', 'jxl', 'jp2'];
    }
}

final class ImagesPageSize
{
    const A4 = 'a4';
    const LETTER = 'letter';
    const AUTO = 'auto';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['a4', 'letter', 'auto'];
    }
}

final class ImagesPdfMode
{
    const PERIMAGE = 'perImage';
    const COMBINED = 'combined';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['perImage', 'combined'];
    }
}

final class ImagesRemoveBackgroundMode
{
    const EDGES = 'edges';
    const ALL = 'all';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['edges', 'all'];
    }
}

final class ImagesResizeKernel
{
    const NEAREST = 'nearest';
    const LINEAR = 'linear';
    const CUBIC = 'cubic';
    const MITCHELL = 'mitchell';
    const LANCZOS2 = 'lanczos2';
    const LANCZOS3 = 'lanczos3';
    const MKS2013 = 'mks2013';
    const MKS2021 = 'mks2021';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['nearest', 'linear', 'cubic', 'mitchell', 'lanczos2', 'lanczos3', 'mks2013', 'mks2021'];
    }
}

final class ImagesResizeTo
{
    const A = 'a';
    const B = 'b';
    const NONE = 'none';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['a', 'b', 'none'];
    }
}

final class ImagesRotate
{
    const N = 'N';
    const R = 'R';
    const L = 'L';
    const I = 'I';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['N', 'R', 'L', 'I'];
    }
}

final class ImagesSimilarityMode
{
    const PAIRS = 'pairs';
    const TOFIRST = 'tofirst';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['pairs', 'tofirst'];
    }
}

final class ImagesSymbology
{
    const CODE128 = 'code128';
    const CODE39 = 'code39';
    const EAN13 = 'ean13';
    const EAN8 = 'ean8';
    const UPCA = 'upca';
    const ITF = 'itf';
    const CODABAR = 'codabar';
    const DATAMATRIX = 'datamatrix';
    const PDF417 = 'pdf417';
    const AZTEC = 'aztec';
    const QRCODE = 'qrcode';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['code128', 'code39', 'ean13', 'ean8', 'upca', 'itf', 'codabar', 'datamatrix', 'pdf417', 'aztec', 'qrcode'];
    }
}

final class ImagesTransformBlendMode
{
    const OVER = 'over';
    const MULTIPLY = 'multiply';
    const SCREEN = 'screen';
    const OVERLAY = 'overlay';
    const DARKEN = 'darken';
    const LIGHTEN = 'lighten';
    const COLOR_DODGE = 'color-dodge';
    const COLOR_BURN = 'color-burn';
    const HARD_LIGHT = 'hard-light';
    const SOFT_LIGHT = 'soft-light';
    const DIFFERENCE = 'difference';
    const EXCLUSION = 'exclusion';
    const ADD = 'add';
    const SATURATE = 'saturate';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['over', 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'color-burn', 'hard-light', 'soft-light', 'difference', 'exclusion', 'add', 'saturate'];
    }
}

final class ImagesType
{
    const IMAGE = 'image';
    const TEXT = 'text';
    const COLOR = 'color';
    const GRADIENT = 'gradient';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['image', 'text', 'color', 'gradient'];
    }
}

final class ImagesVectorizeFileType
{
    const SVG = 'svg';
    const PDF = 'pdf';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['svg', 'pdf'];
    }
}

final class MetaDirection
{
    const INPUT = 'input';
    const OUTPUT = 'output';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['input', 'output'];
    }
}

final class MetaFamily
{
    const IMAGE = 'image';
    const DOCUMENT = 'document';
    const DATA = 'data';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['image', 'document', 'data'];
    }
}

final class MetaStatus
{
    const SUPPORTED = 'supported';
    const PLANNED = 'planned';
    const ALL = 'all';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['supported', 'planned', 'all'];
    }
}

final class Orientation
{
    const PORTRAIT = 'portrait';
    const LANDSCAPE = 'landscape';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['portrait', 'landscape'];
    }
}

final class OutputBundle
{
    const ZIP = 'zip';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['zip'];
    }
}

final class PdfsAlignment
{
    const LEFT = 'left';
    const CENTER = 'center';
    const RIGHT = 'right';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['left', 'center', 'right'];
    }
}

final class PdfsAttachmentsOperation
{
    const LIST = 'list';
    const ADD = 'add';
    const EXTRACT = 'extract';
    const REMOVE = 'remove';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['list', 'add', 'extract', 'remove'];
    }
}

final class PdfsBinding
{
    const LEFT = 'left';
    const RIGHT = 'right';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['left', 'right'];
    }
}

final class PdfsBookmarksOperation
{
    const LIST = 'list';
    const SET = 'set';
    const CLEAR = 'clear';
    const TOC = 'toc';
    const LABELS = 'labels';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['list', 'set', 'clear', 'toc', 'labels'];
    }
}

final class PdfsChecksItem
{
    const STRUCTURE = 'structure';
    const FONTS = 'fonts';
    const SIGNATURES = 'signatures';
    const SECURITY = 'security';
    const PDFA = 'pdfa';
    const PDFUA = 'pdfua';
    const ACCESSIBILITY = 'accessibility';
    const PROPERTIES = 'properties';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['structure', 'fonts', 'signatures', 'security', 'pdfa', 'pdfua', 'accessibility', 'properties'];
    }
}

final class PdfsCompareMode
{
    const TEXT = 'text';
    const VISUAL = 'visual';
    const PAGES = 'pages';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['text', 'visual', 'pages'];
    }
}

final class PdfsEngine
{
    const SHARP = 'sharp';
    const QPDF = 'qpdf';
    const GS = 'gs';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['sharp', 'qpdf', 'gs'];
    }
}

final class PdfsExtractOutput
{
    const SINGLE = 'single';
    const PERPAGE = 'perPage';
    const PERRANGE = 'perRange';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['single', 'perPage', 'perRange'];
    }
}

final class PdfsFit
{
    const CONTAIN = 'contain';
    const COVER = 'cover';
    const STRETCH = 'stretch';
    const NONE = 'none';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['contain', 'cover', 'stretch', 'none'];
    }
}

final class PdfsFlattenAnnotations
{
    const ALL = 'all';
    const PRINT = 'print';
    const SCREEN = 'screen';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['all', 'print', 'screen'];
    }
}

final class PdfsFormsFormat
{
    const JSON = 'json';
    const FDF = 'fdf';
    const XFDF = 'xfdf';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['json', 'fdf', 'xfdf'];
    }
}

final class PdfsFormsOperation
{
    const FLATTEN = 'flatten';
    const FILL = 'fill';
    const FILLANDFLATTEN = 'fillAndFlatten';
    const LISTFIELDS = 'listFields';
    const CREATEFIELD = 'createField';
    const EDITFIELD = 'editField';
    const EXPORTDATA = 'exportData';
    const IMPORTDATA = 'importData';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['flatten', 'fill', 'fillAndFlatten', 'listFields', 'createField', 'editField', 'exportData', 'importData'];
    }
}

final class PdfsImageColorSpace
{
    const SRGB = 'srgb';
    const GRAYSCALE = 'grayscale';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['srgb', 'grayscale'];
    }
}

final class PdfsKeyLength
{
    const V_40 = 40;
    const V_128 = 128;
    const V_256 = 256;

    /** @return array<int,string|int> */
    public static function values()
    {
        return [40, 128, 256];
    }
}

final class PdfsLayoutOperation
{
    const CROP = 'crop';
    const RESIZE = 'resize';
    const BOOKLET = 'booklet';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['crop', 'resize', 'booklet'];
    }
}

final class PdfsMergeMode
{
    const CONCATENATE = 'concatenate';
    const COLLATE = 'collate';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['concatenate', 'collate'];
    }
}

final class PdfsModify
{
    const ALL = 'all';
    const ANNOTATE = 'annotate';
    const FORM = 'form';
    const ASSEMBLY = 'assembly';
    const NONE = 'none';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['all', 'annotate', 'form', 'assembly', 'none'];
    }
}

final class PdfsNumbering
{
    const ARABIC = 'arabic';
    const ROMAN = 'roman';
    const ROMAN_2 = 'ROMAN';
    const ALPHA = 'alpha';
    const ALPHA_2 = 'ALPHA';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['arabic', 'roman', 'ROMAN', 'alpha', 'ALPHA'];
    }
}

final class PdfsOcrMode
{
    const AUTO = 'auto';
    const TABLE = 'table';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['auto', 'table'];
    }
}

final class PdfsOcrOutput
{
    const TEXT = 'text';
    const PDF = 'pdf';
    const PDFA = 'pdfa';
    const DOCX = 'docx';
    const JSON = 'json';
    const TSV = 'tsv';
    const HOCR = 'hocr';
    const ALTO = 'alto';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['text', 'pdf', 'pdfa', 'docx', 'json', 'tsv', 'hocr', 'alto'];
    }
}

final class PdfsOp
{
    const ROTATE = 'rotate';
    const DELETEPAGES = 'deletePages';
    const REORDER = 'reorder';
    const SETPAGESIZE = 'setPageSize';
    const SETCROPBOX = 'setCropBox';
    const INSERTBLANKPAGE = 'insertBlankPage';
    const INSERTIMAGE = 'insertImage';
    const DRAWSHAPE = 'drawShape';
    const ADDLINK = 'addLink';
    const ADDBOOKMARK = 'addBookmark';
    const ANNOTATE = 'annotate';
    const SETVIEWERPREFERENCES = 'setViewerPreferences';
    const NUP = 'nUp';
    const SPLITPAGE = 'splitPage';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['rotate', 'deletePages', 'reorder', 'setPageSize', 'setCropBox', 'insertBlankPage', 'insertImage', 'drawShape', 'addLink', 'addBookmark', 'annotate', 'setViewerPreferences', 'nUp', 'splitPage'];
    }
}

final class PdfsOrientation
{
    const AUTO = 'auto';
    const PORTRAIT = 'portrait';
    const LANDSCAPE = 'landscape';
    const KEEP = 'keep';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['auto', 'portrait', 'landscape', 'keep'];
    }
}

final class PdfsPatternsItem
{
    const EMAIL = 'email';
    const SSN = 'ssn';
    const CREDITCARD = 'creditCard';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['email', 'ssn', 'creditCard'];
    }
}

final class PdfsPdfaFlavour
{
    const AUTO = 'auto';
    const V_1A = '1a';
    const V_1B = '1b';
    const V_2A = '2a';
    const V_2B = '2b';
    const V_2U = '2u';
    const V_3A = '3a';
    const V_3B = '3b';
    const V_3U = '3u';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['auto', '1a', '1b', '2a', '2b', '2u', '3a', '3b', '3u'];
    }
}

final class PdfsPrint
{
    const FULL = 'full';
    const LOW = 'low';
    const NONE = 'none';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['full', 'low', 'none'];
    }
}

final class PdfsProfile
{
    const SCREEN = 'screen';
    const EBOOK = 'ebook';
    const PRINTER = 'printer';
    const PREPRESS = 'prepress';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['screen', 'ebook', 'printer', 'prepress'];
    }
}

final class PdfsSecurityOperation
{
    const ENCRYPT = 'encrypt';
    const DECRYPT = 'decrypt';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['encrypt', 'decrypt'];
    }
}

final class PdfsSplitMode
{
    const EVERY = 'every';
    const BOOKMARKS = 'bookmarks';
    const BLANK = 'blank';
    const SIZE = 'size';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['every', 'bookmarks', 'blank', 'size'];
    }
}

final class PdfsTablesFormat
{
    const JSON = 'json';
    const CSV = 'csv';
    const XLSX = 'xlsx';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['json', 'csv', 'xlsx'];
    }
}

final class PdfsTextFormat
{
    const TEXT = 'text';
    const MARKDOWN = 'markdown';
    const HTML = 'html';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['text', 'markdown', 'html'];
    }
}

final class PdfsToImagesFormat
{
    const JPEG = 'jpeg';
    const JPG = 'jpg';
    const PNG = 'png';
    const WEBP = 'webp';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['jpeg', 'jpg', 'png', 'webp'];
    }
}

final class PdfsType
{
    const TEXT = 'text';
    const CHECKBOX = 'checkbox';
    const RADIO = 'radio';
    const DROPDOWN = 'dropdown';
    const OPTIONLIST = 'optionList';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['text', 'checkbox', 'radio', 'dropdown', 'optionList'];
    }
}

final class PdfsWatermarkMode
{
    const STAMP = 'stamp';
    const OVERLAY = 'overlay';
    const UNDERLAY = 'underlay';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['stamp', 'overlay', 'underlay'];
    }
}

final class RasterFormat
{
    const PNG = 'png';
    const JPEG = 'jpeg';
    const WEBP = 'webp';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['png', 'jpeg', 'webp'];
    }
}

final class RenderChartType
{
    const BAR = 'bar';
    const LINE = 'line';
    const AREA = 'area';
    const SCATTER = 'scatter';
    const PIE = 'pie';
    const DONUT = 'donut';
    const STACKED_BAR = 'stacked-bar';
    const GROUPED_BAR = 'grouped-bar';
    const HISTOGRAM = 'histogram';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['bar', 'line', 'area', 'scatter', 'pie', 'donut', 'stacked-bar', 'grouped-bar', 'histogram'];
    }
}

final class RenderColorScheme
{
    const LIGHT = 'light';
    const DARK = 'dark';
    const NO_PREFERENCE = 'no-preference';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['light', 'dark', 'no-preference'];
    }
}

final class RenderDevice
{
    const IPHONE_SE = 'iphone-se';
    const IPHONE_14 = 'iphone-14';
    const IPHONE_14_PRO_MAX = 'iphone-14-pro-max';
    const PIXEL_7 = 'pixel-7';
    const GALAXY_S20 = 'galaxy-s20';
    const IPAD = 'ipad';
    const IPAD_PRO = 'ipad-pro';
    const ANDROID_TABLET = 'android-tablet';
    const LAPTOP = 'laptop';
    const DESKTOP = 'desktop';
    const DESKTOP_4K = 'desktop-4k';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['iphone-se', 'iphone-14', 'iphone-14-pro-max', 'pixel-7', 'galaxy-s20', 'ipad', 'ipad-pro', 'android-tablet', 'laptop', 'desktop', 'desktop-4k'];
    }
}

final class RenderHtmlToImageFormat
{
    const PNG = 'png';
    const JPEG = 'jpeg';
    const WEBP = 'webp';
    const AVIF = 'avif';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['png', 'jpeg', 'webp', 'avif'];
    }
}

final class RenderHtmlToPdfFormat
{
    const A0 = 'A0';
    const A1 = 'A1';
    const A2 = 'A2';
    const A3 = 'A3';
    const A4 = 'A4';
    const A5 = 'A5';
    const A6 = 'A6';
    const LETTER = 'Letter';
    const LEGAL = 'Legal';
    const TABLOID = 'Tabloid';
    const LEDGER = 'Ledger';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'Letter', 'Legal', 'Tabloid', 'Ledger'];
    }
}

final class RenderMediaType
{
    const SCREEN = 'screen';
    const PRINT = 'print';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['screen', 'print'];
    }
}

final class RenderOutput
{
    const SVG = 'svg';
    const PNG = 'png';
    const JPEG = 'jpeg';
    const WEBP = 'webp';
    const AVIF = 'avif';
    const PDF = 'pdf';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['svg', 'png', 'jpeg', 'webp', 'avif', 'pdf'];
    }
}

final class RenderPdfa
{
    const V_1B = '1b';
    const V_2B = '2b';
    const V_3B = '3b';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['1b', '2b', '3b'];
    }
}

final class RenderSameSite
{
    const STRICT = 'Strict';
    const LAX = 'Lax';
    const NONE = 'None';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['Strict', 'Lax', 'None'];
    }
}

final class RenderStyle
{
    const NORMAL = 'normal';
    const ITALIC = 'italic';
    const OBLIQUE = 'oblique';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['normal', 'italic', 'oblique'];
    }
}

final class RenderTheme
{
    const LIGHT = 'light';
    const DARK = 'dark';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['light', 'dark'];
    }
}

final class RenderType
{
    const IMAGE = 'image';
    const STYLESHEET = 'stylesheet';
    const SCRIPT = 'script';
    const FONT = 'font';
    const MEDIA = 'media';
    const XHR = 'xhr';
    const FETCH = 'fetch';
    const WEBSOCKET = 'websocket';
    const OTHER = 'other';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['image', 'stylesheet', 'script', 'font', 'media', 'xhr', 'fetch', 'websocket', 'other'];
    }
}

final class RenderWaitUntil
{
    const LOAD = 'load';
    const DOMCONTENTLOADED = 'domcontentloaded';
    const NETWORKIDLE0 = 'networkidle0';
    const NETWORKIDLE2 = 'networkidle2';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['load', 'domcontentloaded', 'networkidle0', 'networkidle2'];
    }
}

final class ResizeFit
{
    const FIT = 'fit';
    const COVER = 'cover';
    const PAD = 'pad';
    const FORCE = 'force';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['fit', 'cover', 'pad', 'force'];
    }
}

final class Response
{
    const URL = 'url';
    const BINARY = 'binary';
    const BASE64 = 'base64';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['url', 'binary', 'base64'];
    }
}

final class TemplatesKind
{
    const HTML = 'html';
    const DOCX = 'docx';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['html', 'docx'];
    }
}

final class TemplatesPostKind
{
    const HTML = 'html';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['html'];
    }
}

final class UsageRange
{
    const PERIOD = 'period';
    const V_7D = '7d';
    const V_30D = '30d';
    const MONTH = 'month';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['period', '7d', '30d', 'month'];
    }
}

final class WatermarkPosition
{
    const CENTER = 'center';
    const TOP_LEFT = 'top-left';
    const TOP_RIGHT = 'top-right';
    const BOTTOM_LEFT = 'bottom-left';
    const BOTTOM_RIGHT = 'bottom-right';
    const TOP = 'top';
    const BOTTOM = 'bottom';
    const LEFT = 'left';
    const RIGHT = 'right';

    /** @return array<int,string|int> */
    public static function values()
    {
        return ['center', 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'top', 'bottom', 'left', 'right'];
    }
}
