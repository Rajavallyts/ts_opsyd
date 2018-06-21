<?php

namespace Drupal\collection_relations\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\collection_relations\Entity\CollectionRelationsInterface;

/**
 * Class CollectionRelationsController.
 *
 *  Returns responses for Collection relations routes.
 */
class CollectionRelationsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Collection relations  revision.
   *
   * @param int $collection_relations_revision
   *   The Collection relations  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($collection_relations_revision) {
    $collection_relations = $this->entityManager()->getStorage('collection_relations')->loadRevision($collection_relations_revision);
    $view_builder = $this->entityManager()->getViewBuilder('collection_relations');

    return $view_builder->view($collection_relations);
  }

  /**
   * Page title callback for a Collection relations  revision.
   *
   * @param int $collection_relations_revision
   *   The Collection relations  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($collection_relations_revision) {
    $collection_relations = $this->entityManager()->getStorage('collection_relations')->loadRevision($collection_relations_revision);
    return $this->t('Revision of %title from %date', ['%title' => $collection_relations->label(), '%date' => format_date($collection_relations->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Collection relations .
   *
   * @param \Drupal\collection_relations\Entity\CollectionRelationsInterface $collection_relations
   *   A Collection relations  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(CollectionRelationsInterface $collection_relations) {
    $account = $this->currentUser();
    $langcode = $collection_relations->language()->getId();
    $langname = $collection_relations->language()->getName();
    $languages = $collection_relations->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $collection_relations_storage = $this->entityManager()->getStorage('collection_relations');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $collection_relations->label()]) : $this->t('Revisions for %title', ['%title' => $collection_relations->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all collection relations revisions") || $account->hasPermission('administer collection relations entities')));
    $delete_permission = (($account->hasPermission("delete all collection relations revisions") || $account->hasPermission('administer collection relations entities')));

    $rows = [];

    $vids = $collection_relations_storage->revisionIds($collection_relations);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\collection_relations\CollectionRelationsInterface $revision */
      $revision = $collection_relations_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $collection_relations->getRevisionId()) {
          $link = $this->l($date, new Url('entity.collection_relations.revision', ['collection_relations' => $collection_relations->id(), 'collection_relations_revision' => $vid]));
        }
        else {
          $link = $collection_relations->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.collection_relations.translation_revert', ['collection_relations' => $collection_relations->id(), 'collection_relations_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.collection_relations.revision_revert', ['collection_relations' => $collection_relations->id(), 'collection_relations_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.collection_relations.revision_delete', ['collection_relations' => $collection_relations->id(), 'collection_relations_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['collection_relations_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
