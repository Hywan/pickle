<?php

namespace Pickle\tests\units\Engine\PHP;

use atoum;
use Pickle\tests;

class Ini extends atoum
{
    protected function getEngineMock($path)
    {
        $this->mockGenerator->shuntParentClassCalls();

        $php =  new \mock\Pickle\Engine\PHP();

        $this->calling($php)->__construct = function ($dummy) {};
        $this->calling($php)->getIniPath = function () use ($path) {
            return $path;
        };

        $this->mockGenerator->unshuntParentClassCalls();

        return $php;
    }

    public function test__construct()
    {
        $php = $this->getEngineMock("");
        $this->assert
                ->exception(function () use ($php) {
                        new \Pickle\Engine\PHP\Ini($php);
                    });

        $php = $this->getEngineMock(FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty");
        $this
            ->object(new \Pickle\Engine\PHP\Ini($php))
                ->isInstanceOf("\Pickle\Engine\PHP\Ini");
    }

    public function testupdatePickleSection_empty()
    {
        /* empty file */
        $f = FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty";
        $this
            ->string(file_get_contents($f))
                ->isEmpty();
        $this->do_testupdatePickleSection($f);
    }

    public function testupdatePickleSection_nofooter()
    {
        /* missing pickle section footer*/
        $f = FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.only.sect.begin";
        $this->do_testupdatePickleSection($f);
    }

    public function testupdatePickleSection_simple()
    {
        /* simple file with correct pickle section */
        $f = FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.simple";
        $this->do_testupdatePickleSection($f);
    }

    protected function do_testupdatePickleSection($orig)
    {
        $fl = "$orig.test";
        $fl_exp = "$orig.exp";
        copy($orig, $fl);

        $php = $this->getEngineMock($fl);

        $ini = new \Pickle\Engine\PHP\Ini($php);
        $ini->updatePickleSection(array("php_pumpkin.dll", "php_hello.dll"));

        $this
            ->string(file_get_contents($fl))
                ->isEqualToContentsOfFile($fl_exp);

        unlink($fl);
    }

    public function testrebuildPickleParts_0()
    {
        $php = $this->getEngineMock(FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty");

        $in  = "extension=php_a.dll\n\nextension=php_b.dll\nextension=php_c.dll\n;";
        $exp = "extension=php_a.dll\nextension=php_b.dll";

        $this
            ->if($ini = new \Pickle\Engine\PHP\Ini($php))
            ->then
                ->string(
                    $this->invoke($ini)->rebuildPickleParts($in, array("php_c.dll"))
                )->isEqualTo($exp);
    }

    public function testrebuildPickleParts_1()
    {
        $php = $this->getEngineMock(FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty");

        $in  = "extension=php_a.dll\n;\n;\n\nextension=php_b.dll\nextension=php_c.dll";
        $exp = "extension=php_a.dll\nextension=php_c.dll";

        $this
            ->if($ini = new \Pickle\Engine\PHP\Ini($php))
            ->then
                ->string(
                    $this->invoke($ini)->rebuildPickleParts($in, array("php_b.dll"))
                )->isEqualTo($exp);
    }
}
