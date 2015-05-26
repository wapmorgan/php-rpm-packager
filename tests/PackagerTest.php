<?php
require_once __DIR__.'/../vendor/autoload.php';

use wapmorgan\rpm\Packager;
use wapmorgan\rpm\Spec;

class PackagerTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        if (is_dir(__DIR__.'/package'))
            $this->removeDir(__DIR__.'/package');
        mkdir(__DIR__.'/package');
        if (is_dir(__DIR__.'/output'))
            $this->removeDir(__DIR__.'/output');
        mkdir(__DIR__.'/output');
    }

    public function tearDown() {
        $this->removeDir(__DIR__.'/package');
        $this->removeDir(__DIR__.'/output');
    }

    public function testComplex() {
        exec('command -v rpm', $output, $result);
        if (empty($output)) {
            $this->markTestSkipped('This test can not be performed on a system without rpm');
        }

        mkdir(__DIR__.'/package/test', 0755, true);
        touch(__DIR__.'/package/test/binary');
        mkdir(__DIR__.'/package/test2');
        touch(__DIR__.'/package/test2/abc');

        $spec = new Spec();
        $spec->setPackageName('test')->setRelease(2);
        $packager = new Packager();
        $packager->setSpec($spec);

        $packager->setOutputPath(__DIR__.'/output');
        $packager->addMount(__DIR__.'/package/test/binary', '/usr/bin/binary');
        $packager->addMount(__DIR__.'/package/test2/', '/usr/lib/test/');
        $packager->run();

        $this->assertEquals('%autosetup -c package', $spec->prep);
        $this->assertEquals('rm -rf %{buildroot}'."\n".'mkdir -p %{buildroot}'."\n".'mkdir -p %{buildroot}/usr/bin'."\n".'cp -p usr/bin/binary %{buildroot}/usr/bin/binary'."\n".'mkdir -p %{buildroot}/usr/lib/test/'."\n".'cp -rp usr/lib/test/* %{buildroot}/usr/lib/test/', $spec->install);
        $this->assertEquals('/usr/bin/binary'."\n".'/usr/lib/test/', $spec->files);
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec'));
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar'));

        $phar = new PharData($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');
        $this->assertTrue(isset($phar['usr/bin/binary']));
        $this->assertTrue(isset($phar['usr/lib/test/abc']));

        $command = $packager->build();
        $this->assertEquals('rpmbuild -bb '.$_SERVER['HOME'].'/rpmbuild/SPECS/test.spec', $command);
        exec($command, $_, $result);
        $this->assertEquals(0, $result);
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/RPMS/noarch/test-0.1-2.noarch.rpm'));
        $this->assertTrue($packager->movePackage(__DIR__.'/test-0.1.rpm'));
        $this->assertTrue(file_exists(__DIR__.'/test-0.1.rpm'));

        unlink(__DIR__.'/test-0.1.rpm');
        unlink($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec');
        unlink($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');
    }

    public function testSimple() {
        exec('command -v rpm', $output, $result);
        if (empty($output)) {
            $this->markTestSkipped('This test can not be performed on a system without rpm');
        }

        mkdir(__DIR__.'/package/test', 0755, true);
        touch(__DIR__.'/package/test/binary');
        mkdir(__DIR__.'/package/test2');
        touch(__DIR__.'/package/test2/abc');

        $spec = new Spec();
        $spec->setPackageName('test');
        $packager = new Packager();
        $packager->setSpec($spec);

        $packager->setOutputPath(__DIR__.'/output');
        $packager->addMount(__DIR__.'/package/', '/usr/share/test/');
        $packager->run();

        $this->assertEquals('%autosetup -c package', $spec->prep);
        $this->assertEquals('rm -rf %{buildroot}'."\n".'mkdir -p %{buildroot}'."\n".'mkdir -p %{buildroot}/usr/share/test/'."\n".'cp -rp usr/share/test/* %{buildroot}/usr/share/test/', $spec->install);
        $this->assertEquals('/usr/share/test/', $spec->files);
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec'));
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar'));

        $phar = new PharData($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');
        $this->assertTrue(isset($phar['usr/share/test/test/binary']));
        $this->assertTrue(isset($phar['usr/share/test/test2/abc']));

        $command = $packager->build();
        $this->assertEquals('rpmbuild -bb '.$_SERVER['HOME'].'/rpmbuild/SPECS/test.spec', $command);
        exec($command, $_, $result);
        $this->assertEquals(0, $result);
        $this->assertTrue(file_exists($_SERVER['HOME'].'/rpmbuild/RPMS/noarch/test-0.1-1.noarch.rpm'));
        $this->assertTrue($packager->movePackage(__DIR__.'/test-0.1.rpm'));
        $this->assertTrue(file_exists(__DIR__.'/test-0.1.rpm'));

        unlink(__DIR__.'/test-0.1.rpm');
        unlink($_SERVER['HOME'].'/rpmbuild/SPECS/test.spec');
        unlink($_SERVER['HOME'].'/rpmbuild/SOURCES/test.tar');
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