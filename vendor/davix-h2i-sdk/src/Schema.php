<?php
/**
 * The metadata API. An EndpointSchema is a read-only view of one endpoint of the Davix H2I contract
 * (bundled with this SDK, or the live copy from GET /v1/contract after $client->refreshContract()).
 *
 *   $s = $h2i->schema('images.resize');
 *   $s->fields['width']->minimum;          // 1
 *   $s->fields['resizeMode']->enum;        // ['fit', 'cover', 'pad', 'force']
 *   $s->fields['width']->behavior;         // how the SERVER treats a bad value
 *   $s->validate(['width' => 'x']);        // ['valid' => ..., 'errors' => [...], 'warnings' => [...]]
 *
 * validate() separates what the server REJECTS (errors) from what it silently CLAMPS or replaces by a
 * default (warnings), because that is how Davix H2I really behaves. It is advisory: the server is authoritative.
 */

namespace Davix\H2I\Schema;

final class FieldSchema
{
    /** @var array full contract definition of the field */
    public $raw;
    public $name;
    public $location;
    public $type;
    public $required;
    public $nullable;
    public $description;
    public $default;
    public $example;
    public $enum;
    public $enumName;
    public $format;
    public $minimum;
    public $maximum;
    public $exclusiveMinimum;
    public $exclusiveMaximum;
    public $minLength;
    public $maxLength;
    public $minItems;
    public $maxItems;
    public $pattern;
    public $encoding;
    public $file;
    public $behavior;
    public $requiredWhen;
    public $dependsOn;
    public $conflictsWith;
    public $aliases;
    public $dynamicLimit;
    public $deprecated;
    public $standard;
    /** @var FieldSchema|null */
    public $items;
    /** @var FieldSchema[] keyed by property name */
    public $properties;
    /** @var string */
    private $path;

    public function __construct(array $def, $path)
    {
        $this->raw = $def;
        $this->path = $path;
        foreach (['name', 'location', 'type', 'description', 'default', 'example', 'enum', 'enumName', 'format', 'minimum', 'maximum',
            'exclusiveMinimum', 'exclusiveMaximum', 'minLength', 'maxLength', 'minItems', 'maxItems', 'pattern', 'encoding', 'file',
            'behavior', 'requiredWhen', 'dependsOn', 'conflictsWith', 'aliases', 'dynamicLimit'] as $k) {
            $this->$k = array_key_exists($k, $def) ? $def[$k] : null;
        }
        $this->required = !empty($def['required']);
        $this->nullable = !empty($def['nullable']);
        $this->deprecated = !empty($def['deprecated']);
        $this->standard = !empty($def['standard']);
        $this->items = isset($def['items']) ? new FieldSchema($def['items'], $path . '[]') : null;
        $this->properties = [];
        foreach (isset($def['properties']) ? $def['properties'] : [] as $p) {
            $this->properties[$p['name']] = new FieldSchema($p, $path . '.' . $p['name']);
        }
    }

    /** @return array{valid:bool,errors:array,warnings:array} */
    public function validate($value)
    {
        $errors = [];
        $warnings = [];
        Validator::check($this, $value, $this->path, $errors, $warnings);
        return ['valid' => count($errors) === 0, 'errors' => $errors, 'warnings' => $warnings];
    }
}

final class Validator
{
    private static function push(array &$list, $path, $rule, $message, FieldSchema $f)
    {
        $item = ['path' => $path, 'rule' => $rule, 'message' => $message];
        if (is_array($f->behavior) && (isset($f->behavior['note']) || isset($f->behavior['onOutOfRange']))) {
            $item['behavior'] = $f->behavior;
        }
        $list[] = $item;
    }

    private static function add(array &$errors, array &$warnings, $which, $path, $rule, $message, FieldSchema $f)
    {
        if ($which === 'warnings') {
            self::push($warnings, $path, $rule, $message, $f);
        } else {
            self::push($errors, $path, $rule, $message, $f);
        }
    }

    private static function isFileLike($v)
    {
        if (is_resource($v) || $v instanceof \CURLFile || $v instanceof \SplFileInfo) {
            return true;
        }
        return is_array($v) && (isset($v['path']) || isset($v['data']));
    }

    public static function check(FieldSchema $f, $value, $path, array &$errors, array &$warnings)
    {
        if ($value === null) {
            if ($f->required && !$f->nullable) {
                self::push($errors, $path, 'required', $path . ' is required.', $f);
            }
            return;
        }
        $b = is_array($f->behavior) ? $f->behavior : [];
        $range = isset($b['onOutOfRange']) && in_array($b['onOutOfRange'], ['clamp', 'fallback', 'passthrough'], true) ? 'warnings' : 'errors';
        $invalid = isset($b['onInvalid']) && in_array($b['onInvalid'], ['ignore', 'fallback', 'normalize', 'passthrough'], true) ? 'warnings' : 'errors';

        switch ($f->type) {
            case 'string':
                if (!is_string($value)) {
                    self::add($errors, $warnings, $invalid, $path, 'type', $path . ' must be a string.', $f);
                } else {
                    if ($f->minLength !== null && strlen($value) < $f->minLength) {
                        self::add($errors, $warnings, $range, $path, 'minLength', $path . ' must be at least ' . $f->minLength . ' characters.', $f);
                    }
                    if ($f->maxLength !== null && strlen($value) > $f->maxLength) {
                        self::add($errors, $warnings, $range, $path, 'maxLength', $path . ' must be at most ' . $f->maxLength . ' characters.', $f);
                    }
                    if ($f->pattern !== null && @preg_match('/' . str_replace('/', '\/', $f->pattern) . '/', $value) === 0) {
                        self::add($errors, $warnings, $invalid, $path, 'pattern', $path . ' has an invalid format.', $f);
                    }
                    if (is_array($f->enum) && !in_array($value, $f->enum, true) && !in_array(strtolower($value), $f->enum, true)) {
                        self::add($errors, $warnings, $invalid, $path, 'enum', $path . ' must be one of: ' . implode(', ', $f->enum) . '.', $f);
                    }
                }
                break;
            case 'integer':
            case 'number':
                $n = $value;
                if (is_string($n) && is_numeric($n)) {
                    $n = $n + 0;
                }
                if (!(is_int($n) || is_float($n)) || !is_finite((float) $n)) {
                    self::add($errors, $warnings, $invalid, $path, 'type', $path . ' must be a number.', $f);
                    break;
                }
                if ($f->type === 'integer' && (float) floor($n) !== (float) $n) {
                    self::add($errors, $warnings, $invalid, $path, 'type', $path . ' must be an integer.', $f);
                    break;
                }
                if (is_array($f->enum) && !in_array($n, $f->enum)) {
                    self::add($errors, $warnings, $invalid, $path, 'enum', $path . ' must be one of: ' . implode(', ', $f->enum) . '.', $f);
                }
                if ($f->minimum !== null && $n < $f->minimum) {
                    self::add($errors, $warnings, $range, $path, 'minimum', $path . ' must be >= ' . $f->minimum . '.', $f);
                }
                if ($f->maximum !== null && $n > $f->maximum) {
                    self::add($errors, $warnings, $range, $path, 'maximum', $path . ' must be <= ' . $f->maximum . '.', $f);
                }
                if ($f->exclusiveMinimum !== null && $n <= $f->exclusiveMinimum) {
                    self::add($errors, $warnings, $range, $path, 'exclusiveMinimum', $path . ' must be > ' . $f->exclusiveMinimum . '.', $f);
                }
                if ($f->exclusiveMaximum !== null && $n >= $f->exclusiveMaximum) {
                    self::add($errors, $warnings, $range, $path, 'exclusiveMaximum', $path . ' must be < ' . $f->exclusiveMaximum . '.', $f);
                }
                break;
            case 'boolean':
                if (!is_bool($value) && $value !== 'true' && $value !== 'false') {
                    self::add($errors, $warnings, $invalid, $path, 'type', $path . ' must be a boolean.', $f);
                }
                break;
            case 'file':
                $list = (is_array($value) && !isset($value['path']) && !isset($value['data'])) ? $value : [$value];
                $multiple = is_array($f->file) && !empty($f->file['multiple']);
                if (!$multiple && count($list) > 1) {
                    self::push($errors, $path, 'maxFiles', $path . ' accepts a single file.', $f);
                }
                if (is_array($f->file) && isset($f->file['minFiles']) && count($list) < $f->file['minFiles']) {
                    self::push($errors, $path, 'minFiles', $path . ' needs at least ' . $f->file['minFiles'] . ' files.', $f);
                }
                if (is_array($f->file) && isset($f->file['maxFiles']) && count($list) > $f->file['maxFiles']) {
                    self::push($errors, $path, 'maxFiles', $path . ' accepts at most ' . $f->file['maxFiles'] . ' files.', $f);
                }
                foreach ($list as $item) {
                    if (!is_string($item) && !self::isFileLike($item)) {
                        self::push($errors, $path, 'type', $path . ' must be a file path, stream, CURLFile or [data, filename].', $f);
                    }
                }
                break;
            case 'array':
                $arr = $value;
                if (is_string($arr) && $f->encoding === 'json-string') {
                    $arr = json_decode($arr, true);
                    if (!is_array($arr)) {
                        self::add($errors, $warnings, $invalid, $path, 'type', $path . ' must be JSON.', $f);
                        break;
                    }
                }
                if (!is_array($arr) || (count($arr) && array_keys($arr) !== range(0, count($arr) - 1))) {
                    self::add($errors, $warnings, $invalid, $path, 'type', $path . ' must be a list.', $f);
                    break;
                }
                if ($f->minItems !== null && count($arr) < $f->minItems) {
                    self::add($errors, $warnings, $range, $path, 'minItems', $path . ' needs at least ' . $f->minItems . ' items.', $f);
                }
                if ($f->maxItems !== null && count($arr) > $f->maxItems) {
                    self::add($errors, $warnings, $range, $path, 'maxItems', $path . ' accepts at most ' . $f->maxItems . ' items.', $f);
                }
                if ($f->items !== null) {
                    foreach ($arr as $i => $item) {
                        self::check($f->items, $item, $path . '[' . $i . ']', $errors, $warnings);
                    }
                }
                break;
            case 'object':
                $obj = $value;
                if (is_string($obj) && $f->encoding === 'json-string') {
                    $obj = json_decode($obj, true);
                }
                if (!is_array($obj)) {
                    self::add($errors, $warnings, $invalid, $path, 'type', $path . ' must be an object.', $f);
                    break;
                }
                foreach ($f->properties as $pname => $prop) {
                    self::check($prop, array_key_exists($pname, $obj) ? $obj[$pname] : null, $path . '.' . $pname, $errors, $warnings);
                }
                break;
        }
    }
}

final class EndpointSchema
{
    public $id;
    public $name;
    public $description;
    public $category;
    public $method;
    public $path;
    public $audience;
    public $auth;
    public $requestContentType;
    public $responseContentType;
    public $responseStatuses;
    public $deprecated;
    public $idempotent;
    public $async;
    public $actions;
    public $errors;
    public $notes;
    public $sdk;
    /** @var FieldSchema[] keyed by wire name */
    public $fields = [];
    /** @var array the raw contract endpoint */
    public $raw;

    public function __construct(array $e)
    {
        $this->raw = $e;
        $this->id = $e['id'];
        $this->name = $e['name'];
        $this->description = isset($e['description']) ? $e['description'] : null;
        $this->category = $e['category'];
        $this->method = $e['method'];
        $this->path = $e['path'];
        $this->audience = $e['audience'];
        $this->auth = $e['auth'];
        $this->requestContentType = $e['request']['contentType'];
        $this->responseContentType = $e['response']['contentType'];
        $this->responseStatuses = $e['response']['statuses'];
        $this->deprecated = !empty($e['deprecated']);
        $this->idempotent = !empty($e['idempotency']['supported']);
        $this->async = !empty($e['platform']['jobEligible']);
        $this->actions = isset($e['actions']) ? $e['actions'] : null;
        $this->errors = isset($e['errors']) ? $e['errors'] : [];
        $this->notes = isset($e['notes']) ? $e['notes'] : null;
        $this->sdk = $e['sdk'];
        foreach ($e['request']['fields'] as $f) {
            $this->fields[$f['name']] = new FieldSchema($f, $f['name']);
        }
    }

    /** @return string[] names of the required fields */
    public function required()
    {
        $out = [];
        foreach ($this->fields as $n => $f) {
            if ($f->required) {
                $out[] = $n;
            }
        }
        return $out;
    }

    /** @return FieldSchema[] fields of one location: path | query | header | form | json */
    public function fieldsIn($location)
    {
        return array_values(array_filter($this->fields, function ($f) use ($location) {
            return $f->location === $location;
        }));
    }

    /** @return FieldSchema[] fields belonging to one action (see $schema->actions) */
    public function fieldsForAction($actionName)
    {
        if (!$this->actions) {
            return [];
        }
        foreach ($this->actions['items'] as $a) {
            if ($a['name'] === $actionName) {
                $out = [];
                foreach ($a['fields'] as $n) {
                    if (isset($this->fields[$n])) {
                        $out[] = $this->fields[$n];
                    }
                }
                return $out;
            }
        }
        return [];
    }

    /** Advisory validation of a params array. The server remains the final authority. */
    public function validate(array $params = [])
    {
        $errors = [];
        $warnings = [];
        foreach ($this->fields as $name => $f) {
            if ($f->standard && $f->location === 'header') {
                continue;
            }
            Validator::check($f, array_key_exists($name, $params) ? $params[$name] : null, $name, $errors, $warnings);
        }
        $unknown = array_values(array_diff(array_keys($params), array_keys($this->fields)));
        return ['valid' => count($errors) === 0, 'errors' => $errors, 'warnings' => $warnings, 'unknown' => $unknown];
    }
}
