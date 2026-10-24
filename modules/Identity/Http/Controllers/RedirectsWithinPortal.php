<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\RedirectResponse;

trait RedirectsWithinPortal
{
    /**
     * Seperti redirect()->intended(), tetapi hanya untuk URL portal: `url.intended` dipakai
     * bersama dengan CP Statamic, jadi customer yang sempat membuka /cp tidak dilempar ke sana.
     */
    protected function redirectToIntended(): RedirectResponse
    {
        $intended = (string) session()->pull('url.intended', '');
        $base = rtrim(route('portal.home'), '/');

        return redirect()->to($intended === $base || str_starts_with($intended, $base.'/') ? $intended : route('portal.dashboard'));
    }
}
