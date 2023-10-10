<?php

/*
 * This file is part of the YesWiki Extension caneditformnotadmin.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Caneditformnotadmin\Controller;

use Tamtamchik\SimpleFlash\Flash;
use YesWiki\Bazar\Controller\FormController as BazarFormController;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\Guard;
use YesWiki\Core\Controller\CsrfTokenController;
use YesWiki\Core\YesWikiController;
use YesWiki\Security\Controller\SecurityController;

class FormController extends BazarFormController
{
    protected $parentFormController;

    public function __construct(
        BazarFormController $parentFormController,
        CsrfTokenController $csrfTokenController,
        FormManager $formManager,
        SecurityController $securityController
    )
    {
        parent::__construct($formManager,$securityController,$csrfTokenController);
        $this->parentFormController = $parentFormController;
    }

    public function displayAll($message)
    {
        $output = parent::displayAll($message);
        if (!$this->securityController->isWikiHibernated()){
            $catch = '/';
            $catch .= preg_quote('<button type="button" class="btn btn-primary" disabled="disabled" data-toggle="tooltip" data-placement="bottom" title="','/');
            $catch .= '[^"]+';
            $catch .= preg_quote('">','/');
            $catch .= '(';
            $catch .= '\\s*';
            $catch .= preg_quote('<i class="fa fa-plus icon-plus icon-white"></i>','/');
            $catch .= '\\s*';
            $catch .= '[^"]+';
            $catch .= '\\s*';
            $catch .= ')';
            $catch .= preg_quote('</button>','/');
            $catch .= '/';

            $replacement = <<<HTML
            <a class="btn btn-primary" href="{$this->wiki->Href('','',['vue'=>'formulaire','action'=>'new'],false)}">
                \$1
            </a>
            HTML;
            $output = preg_replace(
                $catch,
                $replacement,
                $output
            );
        }
        return $output;
    }

    public function create()
    {
        $form = null;

        if (isset($_POST['valider'])) {
            $form = $this->formManager->getFromRawData($_POST);
            if ($this->formIsValid($form)) {
                $this->formManager->create($_POST);
                return $this->wiki->redirect($this->wiki->href('', '', ['vue' => 'formulaire', 'msg' => 'BAZ_NOUVEAU_FORMULAIRE_ENREGISTRE'], false));
            }
        }

        return $this->render("@bazar/forms/forms_form.twig", [
            'form' => $form,
            'formAndListIds' => baz_forms_and_lists_ids(),
            'groupsList' => $this->getGroupsListIfEnabled(),
            'onlyOneEntryOptionAvailable' => $this->formManager->isAvailableOnlyOneEntryOption()
        ]);
    }

    public function update($id)
    {
        if ($this->getService(Guard::class)->isAllowed('saisie_formulaire')) {
            $form = $this->formManager->getOne($id);

            if (isset($_POST['valider'])) {
                $form = $this->formManager->getFromRawData($_POST);
                if ($this->formIsValid($form)) {
                    $this->formManager->update($_POST);
                    return $this->wiki->redirect($this->wiki->href('', '', ['vue' => 'formulaire', 'msg' => 'BAZ_FORMULAIRE_MODIFIE'], false));
                }
            }

            return $this->render("@bazar/forms/forms_form.twig", [
                'form' => $form,
                'formAndListIds' => baz_forms_and_lists_ids(),
                'groupsList' => $this->getGroupsListIfEnabled(),
                'onlyOneEntryOptionAvailable' => $this->formManager->isAvailableOnlyOneEntryOption() && $this->formManager->isAvailableOnlyOneEntryMessage()
            ]);
        } else {
            return $this->wiki->redirect($this->wiki->href('', '', ['vue' => 'formulaire', 'msg' => 'BAZ_NEED_ADMIN_RIGHTS'], false));
        }
    }


    private function formIsValid($form)
    {
        $titleFields = array_filter($form['prepared'], function($field) {
            return $field->getPropertyName() == 'bf_titre';
        });
        if (count($titleFields) == 0) {
            Flash::error(_t('BAZ_FORM_NEED_TITLE'));
            return false;
        }
        return true;
    }

    private function getGroupsListIfEnabled(): ?array
    {
        return $this->wiki->UserIsAdmin()
            ? $this->wiki->GetGroupsList()
            : [];
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(
            [
                $this->parentFormController,
                $name
            ],
            $arguments
        );
    }
}
