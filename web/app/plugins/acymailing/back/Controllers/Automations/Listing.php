<?php

namespace AcyMailing\Controllers\Automations;

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\StepClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
    public function listing(): void
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

        acym_session();
        $_SESSION['massAction'] = ['filters' => [], 'actions' => []];
        acym_setVar('layout', 'listing');
        $pagination = new PaginationHelper();

        $searchFilter = $this->getVarFiltersListing('string', 'automation_search', '');
        $status = $this->getVarFiltersListing('string', 'automation_status', '');
        $tagFilter = $this->getVarFiltersListing('string', 'automation_tag', '');
        $ordering = $this->getVarFiltersListing('string', 'automation_ordering', 'ordering');
        $orderingSortOrder = $this->getVarFiltersListing('string', 'automation_ordering_sort_order', 'asc');

        $automationsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'automation_pagination_page', 1);


        $requestData = [
            'ordering' => $ordering,
            'search' => $searchFilter,
            'elementsPerPage' => $automationsPerPage,
            'offset' => ($page - 1) * $automationsPerPage,
            'tag' => $tagFilter,
            'ordering_sort_order' => $orderingSortOrder,
            'status' => $status,
        ];
        $matchingAutomations = $this->getMatchingElementsFromData($requestData, $status, $page);

        if (!empty($matchingAutomations['elements'])) {
            $stepClass = new StepClass();
            foreach ($matchingAutomations['elements'] as $automation) {
                $automation->type_trigger = 'classic';
                $steps = $stepClass->getStepsByAutomationId((int)$automation->id);
                if (!empty($steps[0]->triggers)) {
                    $triggers = json_decode($steps[0]->triggers, true);
                    if (!empty($triggers['type_trigger'])) {
                        $automation->type_trigger = $triggers['type_trigger'];
                    }
                }
            }
        }

        $pagination->setStatus($matchingAutomations['total']->total, $page, $automationsPerPage);

        $filters = [
            'all' => $matchingAutomations['total']->total,
            'active' => $matchingAutomations['total']->totalActive,
            'inactive' => $matchingAutomations['total']->total - $matchingAutomations['total']->totalActive,
        ];

        $data = [
            'allAutomations' => $matchingAutomations['elements'],
            'pagination' => $pagination,
            'search' => $searchFilter,
            'ordering' => $ordering,
            'tag' => $tagFilter,
            'status' => $status,
            'orderingSortOrder' => $orderingSortOrder,
            'automationNumberPerStatus' => $filters,
        ];

        $this->prepareToolbar($data);

        parent::display($data);
    }

    public function prepareToolbar(array &$data): void
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'automation_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(acym_translation('ACYM_NEW_MASS_ACTION'), ['data-task' => 'edit', 'data-step' => 'action'], 'cog');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit', 'data-step' => 'info'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function duplicate(): void
    {
        acym_checkToken();

        $automations = acym_getVar('int', 'elements_checked');

        if (empty($automations)) {
            $this->listing();

            return;
        }

        $automationClass = new AutomationClass();
        $stepClass = new StepClass();
        $conditionClass = new ConditionClass();
        $actionClass = new ActionClass();

        foreach ($automations as $automationId) {
            $automation = $automationClass->getOneById($automationId);
            $step = $stepClass->getOneStepByAutomationId($automation->id);
            $condition = $conditionClass->getOneByStepId($step->id);

            unset($automation->id);
            unset($step->id);

            $automation->active = 0;
            $automation->name .= '_copy';

            $step->automation_id = $automationClass->save($automation);
            $step->last_execution = '';
            $step->next_execution = '';

            $newStepId = $stepClass->save($step);

            if (!empty($condition)) {
                $action = $actionClass->getOneByConditionId($condition->id);

                unset($condition->id);
                $condition->step_id = $newStepId;
                $newConditionId = $conditionClass->save($condition);

                if (!empty($action)) {
                    unset($action->id);
                    $action->condition_id = $newConditionId;
                    if (!empty($action->actions) && strpos($action->actions, 'acy_add_queue') !== false) {
                        $action->actions = json_decode($action->actions, true);
                        $mailClass = new MailClass();
                        foreach ($action->actions as & $oneAction) {
                            if (!empty($oneAction['acy_add_queue']['mail_id'])) {
                                $newMail = $mailClass->duplicateMail($oneAction['acy_add_queue']['mail_id'], MailClass::TYPE_AUTOMATION);
                                if (!empty($newMail)) {
                                    $oneAction['acy_add_queue']['mail_id'] = $newMail->id;
                                }
                            }
                        }
                        $action->actions = json_encode($action->actions);
                    }
                    $actionClass->save($action);
                }
            }
        }

        $this->listing();
    }

    public function triggerAutomation(): void
    {
        acym_checkToken();

        $automationIds = acym_getVar('int', 'elements_checked');

        if (empty($automationIds)) {
            $this->listing();

            return;
        }

        $automationClass = new AutomationClass();
        $result = $automationClass->triggerManual($automationIds);

        if ($result['executed'] > 0) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_X_AUTOMATIONS_TRIGGERED', $result['executed']), 'info');
        }

        if ($result['skipped'] > 0) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_X_SKIPPED_USER_AUTOMATIONS', $result['skipped']), 'warning');
        }

        $this->listing();
    }

    public function ajaxSetOrdering(): void
    {
        acym_checkToken();

        $order = json_decode(acym_getVar('string', 'order') ?? '[]', true);

        $error = false;
        foreach ($order as $index => $automationId) {
            $query = 'UPDATE #__acym_automation SET `ordering` = '.intval($index + 1).' WHERE `id` = '.intval($automationId);
            $error = acym_query($query) < 0 || $error;
        }

        acym_sendAjaxResponse('', [], !$error);
    }
}
