<?php
namespace wapmorgan\rpm;

use PharData;
use DirectoryIterator;

class Packager {
    private $_spec;
    private $mountPoints = array();
    private $outputPath;

    /**
     * Set the control file
     *
     * This file contains the base package information
     *
     * @param Spec $spec
     * @return \wapmorgan\rpm\Spec
     */
    public function setSpec(Spec $spec)
    {
        $this->_spec = $spec;
        return $this;
    }

    /**
     * Return the actual spec file
     *
     * @return Spec
     */
    public function getSpec()
    {
        return $this->_spec;
    }

    public function setOutputPath($path) {
        $this->outputPath = $path;
        return $this;
    }

    public function getOutputPath() {
        return $this->outputPath;
    }

    public function addMount($sourcePath, $destinationPath) {
        $this->mountPoints[$sourcePath] = $destinationPath;
        return $this;
    }

    public function run() {
        if (!is_dir($_SERVER['HOME'].'/rpmbuild/SOURCES'))
            mkdir($_SERVER['HOME'].'/rpmbuild/SOURCES', 0777);
        if (!is_dir($_SERVER['HOME'].'/rpmbuild/SPECS'))
            mkdir($_SERVER['HOME'].'/rpmbuild/SPECS', 0777);

        if (file_exists($this->getOutputPath())) {
            $iterator = new DirectoryIterator($this->getOutputPath());
            foreach ($iterator as $path) {
                if ($path != '.' || $path != '..') {
                    echo "OUTPUT DIRECTORY MUST BE EMPTY! Something exists, exit immediately!" . PHP_EOL;
                    exit();
                }
            }
        }

        mkdir($this->getOutputPath(), 0777);

        foreach ($this->mountPoints as $path => $dest) {
            $this->pathToPath($path, $this->getOutputPath().DIRECTORY_SEPARATOR.$dest);
        }

        $spec = $this->_spec;
        $spec->setPrep('%autosetup -c package');
        $install_section = 'rm -rf %{buildroot}'."\n".'mkdir -p %{buildroot}'."\n".'cp -rp * %{buildroot}';

        // $created_dirs = array();
        // foreach ($this->mountPoints as $sourcePath => $destinationPath) {
        //     if (is_dir($sourcePath)) {
        //         $dir = dirname($sourcePath);
        //         if (!isset($created_dirs[$dir]))
        //             $install_section .= 'mkdir -p %{buildroot}'.$dir."\n";
        //         $install_section .= 'cp -pr '.$sourcePath.' %{buildroot}'.$destinationPath;
        //         $created_dirs[$dir] = true;
        //     } else {
        //         $dir = dirname($sourcePath);
        //         if (!isset($created_dirs[$dir]))
        //             $install_section .= 'mkdir -p %{buildroot}'.$dir."\n";
        //         $install_section .= 'cp -p '.$sourcePath.' %{buildroot}'.$destinationPath;
        //         $created_dirs[$dir] = true;
        //     }
        // }

        $spec->setInstall($install_section);

        // $files_section = null;
        // foreach ($this->mountPoints as $sourcePath => $destinationPath) {
        //     if (is_dir($sourcePath)) {
        //         $files_section .= '%{buildroot}'.$destinationPath.'/*';
        //     } else {
        //         $files_section .= '%{buildroot}'.$destinationPath;
        //     }
        // }

        // $spec->setFiles($files_section);
        $spec->setFiles('/*');

        $tar = new PharData($_SERVER['HOME'].'/rpmbuild/SOURCES/'.$this->_spec->Name.'.tar');
        $tar->buildFromDirectory($this->outputPath);
        $spec->setKey('Source0', $this->_spec->Name.'.tar');

        file_put_contents($_SERVER['HOME'].'/rpmbuild/SPECS/'.$this->_spec->Name.'.spec', (string)$this->_spec);

        return $this;
    }

    public function build() {
        $command = 'rpmbuild -bb '.$_SERVER['HOME'].'/rpmbuild/SPECS/'.$this->_spec->Name.'.spec';
        return $command;
    }

    private function pathToPath($path, $dest)
    {
        if (is_dir($path)) {
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $element) {
                if ($element != '.' && $element != '..') {
                    $fullPath = $path . DIRECTORY_SEPARATOR . $element;
                    if (is_dir($fullPath)) {
                        $this->pathToPath($fullPath, $dest . DIRECTORY_SEPARATOR . $element);
                    } else {
                        $this->copy($fullPath, $dest . DIRECTORY_SEPARATOR . $element);
                    }
                }
            }
        } else if (is_file($path)) {
            $this->copy($path, $dest);
        }
    }

    private function copy($source, $dest)
    {
        $destFolder = dirname($dest);
        if (!file_exists($destFolder)) {
            mkdir($destFolder, 0777, true);
        }
        copy($source, $dest);
        if (fileperms($source) != fileperms($dest))
            chmod($dest, fileperms($source));
    }
}