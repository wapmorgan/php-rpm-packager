<?php
namespace wapmorgan\rpm;

class Spec {
    private $_keys = array(
        'Name' => '',
        'Version' => '0.1',
        'Release' => '',
        'Summary' => '',
        'Group' => '',
        'License' => '',
        'URL' => '',
        'BuildRequires' => '',
        'Requires' => '',
    );

    private $_blocks = array(
        'description' => '',
        'prep' => '%autosetup',
        'build' => '',
        'install' => 'rm -rf %{buildroot}
mkdir -p %{buildroot}%{_bindir}/
mkdir -p %{buildroot}%{_libdir}/'."\n",
        'files' => '',
        'changelog' => '',
    );

    public function __set($prop, $value) {
        if (isset($this->_keys[$prop]))
            $this->_keys[$prop] = $value;
        else if (isset($this->_blocks[$prop]))
            $this->_blocks[$prop] = $value;
        else
            $this->_keys[$prop] = $value;
    }

    public function __get($prop) {
        if (array_key_exists($prop, $this->_keys))
            return $this->_keys[$prop];
        else if (array_key_exists($prop, $this->_blocks))
            return $this->_blocks[$prop];
    }

    public function __toString() {
        $spec = '';
        foreach ($this->_keys as $key => $value) {
            $spec .= sprintf('%12s %s'."\n", $key.':', $value);
        }

        foreach ($this->_blocks as $block => $value) {
            $spec .= "\n".'%'.$block."\n".$value."\n";
        }
        return $spec;
    }
}