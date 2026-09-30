<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validasi sisi server. Contoh aturan:
 *   'nama' => 'required|max:120', 'email' => 'nullable|email|unique:users,email,5'
 */
final class Validator
{
    private array $errors = [];

    private const LABELS = [
        'required' => ':label wajib diisi.',
        'email' => ':label harus berupa alamat email yang valid.',
        'max' => ':label maksimal :p karakter.',
        'min' => ':label minimal :p karakter.',
        'digits' => ':label harus terdiri dari :p digit angka.',
        'numeric' => ':label harus berupa angka.',
        'integer' => ':label harus berupa bilangan bulat.',
        'in' => ':label tidak valid.',
        'date' => ':label harus berupa tanggal yang valid.',
        'datetime' => ':label harus berupa tanggal dan jam yang valid.',
        'after_or_equal' => ':label tidak boleh lebih awal dari :p.',
        'before' => ':label harus sebelum :p.',
        'unique' => ':label sudah digunakan.',
        'exists' => ':label tidak ditemukan.',
        'phone' => ':label harus berupa nomor telepon yang valid (contoh 08123456789).',
        'regex' => 'Format :label tidak valid.',
        'confirmed' => 'Konfirmasi :label tidak cocok.',
        'accepted' => ':label wajib disetujui.',
        'url' => ':label harus berupa URL yang valid (diawali https://).',
        'code' => ':label hanya boleh huruf kapital, angka, dan tanda hubung.',
        'password' => ':label minimal 8 karakter dan mengandung huruf serta angka.',
    ];

    public function __construct(private array $data, private array $rules, private array $labels = [])
    {
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        $v = new self($data, $rules, $labels);
        $v->run();
        return $v;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $empty = $value === null || $value === '' || $value === [];

            if (in_array('nullable', $rules, true) && $empty) {
                continue;
            }
            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name !== 'required' && $name !== 'accepted' && $empty) {
                    continue;
                }
                if (!$this->check($name, $param, $value, $field)) {
                    $this->addError($field, $this->message($name, $field, $param));
                    break;
                }
            }
        }
    }

    private function check(string $name, ?string $p, mixed $v, string $field): bool
    {
        $s = is_scalar($v) ? (string) $v : '';
        return match ($name) {
            'required' => !($v === null || (is_string($v) && trim($v) === '') || $v === []),
            'accepted' => in_array($v, ['1', 'on', 'yes', 1, true], true),
            'email' => filter_var($s, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($s) <= 160,
            'max' => mb_strlen($s) <= (int) $p,
            'min' => mb_strlen($s) >= (int) $p,
            'digits' => (bool) preg_match('/^\d{' . (int) $p . '}$/', $s),
            'numeric' => is_numeric($s),
            'integer' => filter_var($s, FILTER_VALIDATE_INT) !== false,
            'in' => in_array($s, explode(',', (string) $p), true),
            'date' => self::validDate($s, 'Y-m-d'),
            'datetime' => self::validDate($s, 'Y-m-d\TH:i') || self::validDate($s, 'Y-m-d H:i') || self::validDate($s, 'Y-m-d H:i:s'),
            'after_or_equal' => ($o = $this->data[$p] ?? null) === null || $o === '' || strtotime($s) >= strtotime((string) $o),
            'before' => strtotime($s) < strtotime($p === 'today' ? 'today' : (string) ($this->data[$p] ?? $p)),
            'phone' => (bool) preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', preg_replace('/[\s-]/', '', $s)),
            'regex' => (bool) preg_match((string) $p, $s),
            'confirmed' => $s === (string) ($this->data[$field . '_confirmation'] ?? ''),
            'url' => filter_var($s, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://#i', $s),
            'code' => (bool) preg_match('/^[A-Z0-9-]{2,12}$/', $s),
            'password' => mb_strlen($s) >= 8 && preg_match('/[A-Za-z]/', $s) && preg_match('/\d/', $s),
            'unique' => $this->unique((string) $p, $s),
            'exists' => $this->exists((string) $p, $s),
            default => true,
        };
    }

    private static function validDate(string $s, string $format): bool
    {
        $d = \DateTime::createFromFormat('!' . $format, $s);
        return $d !== false && $d->format($format) === $s;
    }

    /** unique:tabel,kolom[,idKecuali] */
    private function unique(string $p, string $v): bool
    {
        [$table, $col, $except] = array_pad(explode(',', $p), 3, null);
        self::assertIdent($table, $col);
        $sql = "SELECT COUNT(*) FROM `$table` WHERE `$col` = ?";
        $params = [$v];
        if ($except) {
            $sql .= ' AND id <> ?';
            $params[] = (int) $except;
        }
        return (int) Database::value($sql, $params) === 0;
    }

    /** exists:tabel,kolom */
    private function exists(string $p, string $v): bool
    {
        [$table, $col] = array_pad(explode(',', $p), 2, 'id');
        self::assertIdent($table, $col);
        return (int) Database::value("SELECT COUNT(*) FROM `$table` WHERE `$col` = ?", [$v]) > 0;
    }

    private static function assertIdent(string ...$names): void
    {
        foreach ($names as $n) {
            if (!preg_match('/^\w+$/', $n)) {
                throw new \InvalidArgumentException('Nama tabel/kolom tidak valid');
            }
        }
    }

    private function message(string $rule, string $field, ?string $p): string
    {
        $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
        if (in_array($rule, ['after_or_equal', 'before'], true) && $p && isset($this->labels[$p])) {
            $p = mb_strtolower($this->labels[$p]);
        }
        return strtr(self::LABELS[$rule] ?? ':label tidak valid.', [':label' => $label, ':p' => (string) $p]);
    }
}
