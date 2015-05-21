<?php
namespace wapmorgan\rpm;

class Packager {
    private $_spec;
    private $_mountPoints = array();
    private $_outputPath;

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

    /**
     * @deprecated See addMount instead
     */
    public function mount($sourcePath, $destinationPath) {
        return $this->addMount($sourcePath, $destinationPath);
    }

    public function addMount($sourcePath, $destinationPath) {
        $this->_mountPoints[$sourcePath] = $destinationPath;
        return $this;
    }

    public function setOutputPath($path) {
        $this->_outputPath = $path;
        return $this;
    }

    public function getOutputPath() {
        return $this->_outputPath;
    }

    public function run()
    {
        if (file_exists($spec)) {
            $iterator = new \DirectoryIterator($this->getOutputPath());
            foreach ($iterator as $path) {
                if ($path != '.' || $path != '..') {
                    echo "OUTPUT DIRECTORY MUST BE EMPTY! Something exists, exit immediately!" . PHP_EOL;
                    exit();
                }
            }
        }
        if (!is_dir("~/rpmbuild/SOURCES"))
            mkdir("~/rpmbuild/SOURCES", 0777);
        if (!is_dir("~/rpmbuild/SPECS"))
            mkdir("~/rpmbuild/SPECS", 0777);

        mkdir($this->getOutputPath(), 0777);

        foreach ($this->_mountPoints as $path => $dest) {
            $this->_pathToPath($path, $this->getOutputPath() . DIRECTORY_SEPARATOR . $dest);
        }

        $zip = new \ZipArchive();
        $zip->open("~/rpmbuild/SOURCES/".$this->_spec->Name.'-'.$this->_spec->Version.'.zip');
        $zip->addGlob($this->getOutputPath().'/*');
        $zip->close();

        file_put_contents("~/rpmbuild/SPECS/".$this->_spec->Name.'.spec', (string)$this->_spec);

        return $this;
    }

    private function _pathToPath($path, $dest)
    {
        if (is_dir($path)) {
            $iterator = new \DirectoryIterator($path);
            foreach ($iterator as $element) {
                if ($element != '.' && $element != '..') {
                    $fullPath = $path . DIRECTORY_SEPARATOR . $element;
                    if (is_dir($fullPath)) {
                        $this->_pathToPath($fullPath, $dest . DIRECTORY_SEPARATOR . $element);
                    } else {
                        $this->_copy($fullPath, $dest . DIRECTORY_SEPARATOR . $element);
                    }
                }
            }
        } else if (is_file($path)) {
            $this->_copy($path, $dest);
        }
    }

    private function _copy($source, $dest)
    {
        $destFolder = dirname($dest);
        if (!file_exists($destFolder)) {
            mkdir($destFolder, 0777, true);
        }
        copy($source, $dest);
        if (fileperms($source) != fileperms($dest))
            chmod($dest, fileperms($source));
    }

    public function build()
    {

        $command = "cd ~/rpmbuild/SPECS; rpmbuild -ba {$this->_spec->Name}; cd -";

        return $command;
    }
}