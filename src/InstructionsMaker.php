<?php
namespace wapmorgan\rpm;

class InstructionsMaker {
    public function makeInstall(Spec $spec) {
        $install = $spec->install;
        $install .= PHP_EOL.'mkdir -p %{buildroot}%{_libdir}/'.$spec->Name;
    }
}