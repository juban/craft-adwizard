<?php
/**
 * Ad Wizard plugin for Craft CMS
 *
 * Easily manage custom advertisements on your website.
 *
 * @author    Double Secret Agency
 * @link      https://www.doublesecretagency.com/
 * @copyright Copyright (c) 2014 Double Secret Agency
 */

namespace doublesecretagency\adwizard\controllers;

use yii\base\Response;
use yii\web\HttpException;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use doublesecretagency\adwizard\AdWizard;
use doublesecretagency\adwizard\elements\Ad;
use doublesecretagency\adwizard\models\FieldLayout;

/**
 * Class FieldLayoutsController
 * @since 2.1.0
 */
class FieldLayoutsController extends Controller
{

    /**
     * Called before displaying the field layouts page.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $this->requireLogin();

        $fieldLayouts = AdWizard::$plugin->adWizard_fieldLayouts->getFieldLayouts();

        return $this->renderTemplate('ad-wizard/fieldlayouts', [
            'crumbs' => $this->_fieldLayoutsCrumbs(),
            'selectedSubnavItem' => 'fieldlayouts',
            'fullPageForm' => true,
            'fieldLayouts' => $fieldLayouts,
        ]);
    }

    /**
     * Edit a field layout.
     *
     * @param int|null $fieldLayoutId The field layout’s ID, if any.
     * @param FieldLayout|null $fieldLayout The field layout being edited, if there were any validation errors.
     * @return Response
     * @throws HttpException if the requested field layout cannot be found
     */
    public function actionEditFieldLayout(int $fieldLayoutId = null, FieldLayout $fieldLayout = null): Response
    {
        $this->requireLogin();

        if ($fieldLayoutId !== null && !$fieldLayout) {
            $fieldLayout = AdWizard::$plugin->adWizard_fieldLayouts->getLayoutById($fieldLayoutId);

            if (!$fieldLayout) {
                throw new HttpException('Field layout not found');
            }
        }

        if (!$fieldLayout) {
            $fieldLayout = new FieldLayout();
        }

        // Breadcrumbs
        $crumbs = $this->_fieldLayoutsCrumbs();

        // Append final crumb
        if ($fieldLayout->id) {
            $crumbs[] = [
                'label' => Craft::t('site', $fieldLayout->name),
                'url'   => UrlHelper::cpUrl('ad-wizard/fieldlayouts/'.$fieldLayout->id)
            ];
        } else {
            $crumbs[] = [
                'label' => Craft::t('ad-wizard', 'Create New Field Layout'),
                'url'   => UrlHelper::cpUrl('ad-wizard/fieldlayouts/new')
            ];
        }

        return $this->renderTemplate('ad-wizard/fieldlayouts/_edit', [
            'crumbs' => $crumbs,
            'selectedSubnavItem' => 'fieldlayouts',
            'fullPageForm' => true,
            'fieldLayoutId' => $fieldLayoutId,
            'fieldLayout' => $fieldLayout,
        ]);
    }

    /**
     * Save a field layout.
     *
     * @return Response|null
     */
    public function actionSaveFieldLayout()
    {
        $this->requirePostRequest();
        $this->requireLogin();

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Ad::class;

        if (!Craft::$app->getFields()->saveLayout($fieldLayout)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save field layout.'));

            return null;
        }

        $layout = new FieldLayout();

        // Get request
        $request = Craft::$app->getRequest();

        // Get POST values
        $layout->id   = $fieldLayout->id;
        $layout->name = $request->getBodyParam('name');

        // Save it
        if (!AdWizard::$plugin->adWizard_fieldLayouts->saveFieldLayout($layout)) {
            Craft::$app->getSession()->setError(Craft::t('ad-wizard', 'Couldn’t save field layout.'));

            // Send the field layout back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'fieldLayout' => $layout
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('ad-wizard', 'Field layout saved.'));

        return $this->redirectToPostedUrl($layout);
    }

    /**
     * Deletes a field layout.
     *
     * @return Response
     */
    public function actionDeleteFieldLayout(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireLogin();

        $fieldLayoutId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        AdWizard::$plugin->adWizard_fieldLayouts->deleteLayoutById($fieldLayoutId);

        return $this->asJson(['success' => true]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Breadcrumbs for field layout pages.
     *
     * @return array
     */
    private function _fieldLayoutsCrumbs(): array
    {
        return [
            [
                'label' => Craft::t('ad-wizard', 'Ad Wizard'),
                'url'   => UrlHelper::cpUrl('ad-wizard')
            ],
            [
                'label' => Craft::t('ad-wizard', 'Field Layouts'),
                'url'   => UrlHelper::cpUrl('ad-wizard/fieldlayouts')
            ]
        ];
    }

}
