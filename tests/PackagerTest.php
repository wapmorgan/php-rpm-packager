<?php
require_once __DIR__.'/../vendor/autoload.php';

use wapmorgan\rpm\Packager;
use wapmorgan\rpm\Spec;

class PackagerTest extends PHPUnit_Framework_TestCase {
    public function testSimple() {
        exec('command -v rpm', $output, $result);
        if (empty($output)) {
            $this->markTestSkipped('This test can not be performed on a system without rpm');
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
        $this->assertEquals('rm -rf %{buildroot}'."\n".'mkdir -p %{buildroot}'."\n".'cp -rp * %{buildroot}', $spec->install);
        $this->assertEquals('/*', $spec->files);
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec'));
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar'));

        $phar = new PharData($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');
        $this->assertTrue(isset($phar['test/binary']));
        $this->assertTrue(isset($phar['test2/abc']));

        $command = $packager->build();
        $result_file = $packager->getResultFile();
        $this->assertEquals('rpmbuild -bb '.$_SERVER['HOME'].'/rpmbuild/SPECS/test.spec', $command);
        $this->assertEquals($_SERVER['HOME'].'/rpmbuild/RPMS/noarch/test-0.1-1.noarch.rpm', $result_file);
        exec($command, $_, $result);
        $this->assertEquals(0, $result);
        $this->assertTrue(file_exists($result_file));

        unlink($_SERVER['HOME'].'/rpmbuild/RPMS/noarch/test-0.1-1.noarch.rpm');
        unlink($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec');
        unlink($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');

        $this->removeDir($dir);
        $this->removeDir(__DIR__.'/output');
    }

    public function testComplex() {
        exec('command -v rpm', $output, $result);
        if (empty($output)) {
            $this->markTestSkipped('This test can not be performed on a system without rpm');
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
        $packager->addMount($dir.'/test/binary', '/usr/bin/binary');
        $packager->addMount($dir.'/test2/', '/usr/lib/test/');
        $packager->run();

        $this->assertEquals('%autosetup -c package', $spec->prep);
        $this->assertEquals('rm -rf %{buildroot}'."\n".'mkdir -p %{buildroot}'."\n".'cp -rp * %{buildroot}', $spec->install);
        $this->assertEquals('/*', $spec->files);
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec'));
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar'));

        $phar = new PharData($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');
        $this->assertTrue(isset($phar['usr/bin/binary']));
        $this->assertTrue(isset($phar['usr/lib/test/abc']));

        $command = $packager->build();
        $result_file = $packager->getResultFile();
        $this->assertEquals('rpmbuild -bb '.$_SERVER['HOME'].'/rpmbuild/SPECS/test.spec', $command);
        $this->assertEquals($_SERVER['HOME'].'/rpmbuild/RPMS/noarch/test-0.1-1.noarch.rpm', $result_file);
        exec($command, $_, $result);
        $this->assertEquals(0, $result);
        $this->assertTrue(file_exists($result_file));

        unlink($_SERVER['HOME'].'/rpmbuild/RPMS/noarch/test-0.1-1.noarch.rpm');
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