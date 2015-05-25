<?php
require_once __DIR__.'/../vendor/autoload.php';

use wapmorgan\rpm\Packager;
use wapmorgan\rpm\Spec;

class PackagerTest extends PHPUnit_Framework_TestCase {
    public function testSimple() {
        exec('rpm --version', $_, $result);
        if ($result != 0) {
            $this->markSkipped('This test can not be performed on a system without rpm');
        }

        $dir = __DIR__.'/package/';
        if (is_dir($dir))
            $this->removeDir($dir);

        mkdir($dir);
        mkdir($dir.'/test', 0755, true);
        touch($dir.'/test/binary');
        mkdir($dir.'/test2');
        touch($dir.'/test2/abc');

        $spec = new Spec();
        $spec->setPackageName('test');
        $packager = new Packager();
        $packager->setSpec($spec);

        if (is_dir(__DIR__.'/output'))
            $this->removeDir(__DIR__.'/output');

        $packager->setOutputPath(__DIR__.'/output');
        $packager->addMount($dir, '/');
        $packager->run();
        $this->assertEquals('%autosetup -c package', $spec->prep);
        $this->assertEquals('rm -rf %{buildroot}'."\n".'cp -rp * %{buildroot}', $spec->install);
        $this->assertEquals('%{buildroot}/*', $spec->files);
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec'));
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar'));

        $phar = new PharData($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');
        $this->assertTrue(isset($phar['test/binary']));
        $this->assertTrue(isset($phar['test2/abc']));
        unlink($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec');
        unlink($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');

        $this->removeDir($dir);
        $this->removeDir(__DIR__.'/output');
    }

    private function removeDir($dir) {
        $dd = opendir($dir);
        while (($file = readdir($dd)) !== false) {
            if (in_array($file, array('.', '..'))) continue;
            if (is_dir($dir.'/'.$file))
                $this->removeDir($dir.'/'.$file);
            else
                unlink($dir.'/'.$file);
        }
        closedir($dd);
        rmdir($dir);
    }
}