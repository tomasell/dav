<?php
namespace Bread\DAV\Helpers;

/**
 *  RFC 4122
 */
class UUID
{

    /**
     * Generate a version 4 (random) UUID.
     *
     * @return Uuid
     */
    public static function uuid4()
    {
        $bytes = static::generateBytes(16);
        $hex = bin2hex($bytes);
        return vsprintf('%08s-%04s-%04s-%02s%02s-%012s', static::uuidFromHashedName($hex, 4));
    }

    /**
     *
     * @param int $length
     * @return string
     */
    protected static function generateBytes($length)
    {
        $bytes = '';
        foreach (range(1, $length) as $i) {
            $bytes = chr(mt_rand(0, 256)) . $bytes;
        }
        return $bytes;
    }

    /**
     * Returns a version 3 or 5 UUID based on the hash (md5 or sha1) of a
     * namespace identifier (which is a UUID) and a name (which is a string)
     *
     * @param string $hash
     *            The hash to use when creating the UUID
     * @param int $version
     *            The UUID version to be generated
     * @return Uuid
     */
    protected static function uuidFromHashedName($hash, $version)
    {
        // Set the version number
        $timeHi = hexdec(substr($hash, 12, 4)) & 0x0fff;
        $timeHi &= ~ (0xf000);
        $timeHi |= $version << 12;
        // Set the variant to RFC 4122
        $clockSeqHi = hexdec(substr($hash, 16, 2)) & 0x3f;
        $clockSeqHi &= ~ (0xc0);
        $clockSeqHi |= 0x80;
        $fields = array(
            'time_low' => substr($hash, 0, 8),
            'time_mid' => substr($hash, 8, 4),
            'time_hi_and_version' => sprintf('%04x', $timeHi),
            'clock_seq_hi_and_reserved' => sprintf('%02x', $clockSeqHi),
            'clock_seq_low' => substr($hash, 18, 2),
            'node' => substr($hash, 20, 12)
        );
        return $fields;
    }
}