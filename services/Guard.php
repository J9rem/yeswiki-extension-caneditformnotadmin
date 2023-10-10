<?php

/*
 * This file is part of the YesWiki Extension caneditformnotadmin.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Caneditformnotadmin\Service;

use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\Guard as BazarGuard;
use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\UserManager;
use YesWiki\Wiki;

class Guard extends BazarGuard
{
    protected $parentGuard;

    public function __construct(
        AclService $aclService,
        AuthController $authController,
        BazarGuard $parentGuard,
        FormManager $formManager,
        UserManager $userManager,
        Wiki $wiki
    ) {
        parent::__construct($aclService,$authController,$formManager,$userManager,$wiki);
        $this->parentGuard = $parentGuard;
    }

    // TODO remove this method and use YesWiki::HasAccess
    public function isAllowed($action = 'saisie_fiche', $ownerId = '') : bool
    {
        $loggedUserName = $this->authController->getLoggedUserName();
        $isOwner = $ownerId === $loggedUserName || $ownerId === '';

        // Admins are allowed all actions
        if ($this->userManager->isInGroup('admins')) {
            return true;
        }

        switch ($action) {
            case 'supp_fiche':
                // it should not be possible to delete a file if not connected even if no owner (prevent spam)
                return $ownerId != '' && $isOwner;
            case 'voir_champ':
                return $isOwner;

            case 'modif_fiche':
            case 'saisie_fiche':
            case 'saisie_formulaire':
            case 'saisie_liste':
            case 'voir_mes_fiches':
                return true;

            case 'valider_fiche':
            default:
                return false;
        }
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(
            [
                $this->parentGuard,
                $name
            ],
            $arguments
        );
    }
}
