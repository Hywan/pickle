<?php

namespace Pickle\Package\Convey\Command;

use Composer\Package\Version\VersionParser;

class Type
{
    const PICKLE = 'pickle';
    const PECL = 'pecl';
    const GIT = 'git';
    const TGZ = 'tgz';
    const SRC_DIR = 'srcdir';
    const ANY = 'any';

    public static function match($regs, $arg, &$matches)
    {
        foreach ($regs as $reg) {
            $ret = preg_match($reg, $arg, $matches);
            if ($ret > 0) {
                return $ret;
            }
        }

        return 0;
    }

    public static function determinePickle($arg, &$matches)
    {
        $versionParser = new VersionParser();
        $res = $versionParser->parseNameVersionPairs([$arg]);
        $argPrefix = substr($arg, 0, 1);
        if ($argPrefix == '/' || $argPrefix == '.') {
            return 0;
        }
        $matches = [
                'package' => $res[0]['name'],
                'version' => isset($res[0]['version']) ? $res[0]['version'] : '',
            ];

        return 1;
    }

    public static function determinePecl($arg, &$matches)
    {
        $reg0 = '#^
            (?:pecl/)?
            (?<package>\w+)
            (?:
                \-(?<stability>beta|stable|alpha)
            )?
        $#x';

        $reg1 = '#^
            (?:pecl/)?
            (?<package>\w+)
            (?:
                (\-|@)(?<version>(?:\d+(?:\.\d+){1,2})|(?:[1-2]\d{3}[0-1]\d[0-3]\d{1}))
            )?
        $#x';

        return self::match([$reg0, $reg1], $arg, $matches);
    }

    /* XXX definitely needs a serious improvement */
    public static function determineGit($arg, &$matches)
    {
        $reg0 = '#^
            (?:git|https|http|ssh|rsync|file?)(://|@).*?(/|\:)
            (?P<package>[a-zA-Z0-9\-_]+)
            (?:
                (?:\.git|)
                (?:\#(?P<reference>.*?)|)
            )?
        $#x';

        return self::match([$reg0], $arg, $matches);
    }

    public static function determine($path, $remote)
    {
        if ('.tgz' == substr($path, -4) || '.tar.gz' == substr($path, -7)) {
            return self::TGZ;
        } elseif ($remote && self::determinePecl($path, $matches) > 0) {
            return self::PECL;
        } elseif ($remote && self::determineGit($path, $matches) > 0) {
            return self::GIT;
        } elseif (!$remote && is_dir($path)) {
            return self::SRC_DIR;
        } elseif (self::determinePickle($path, $matches) > 0) {
            return self::PICKLE;
        }

        return self::ANY;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
