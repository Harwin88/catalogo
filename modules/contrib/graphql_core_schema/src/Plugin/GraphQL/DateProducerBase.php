<?php

namespace Drupal\graphql_core_schema\Plugin\GraphQL;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for formatted date producers.
 */
abstract class DateProducerBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition,
  ) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('date.formatter')
    );
  }

  /**
   * The constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    DateFormatterInterface $dateFormatter,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Get a Drupal DateTime object.
   *
   * @param string|int|\Drupal\Core\Datetime\DrupalDateTime|null $value
   *   The date input in various formats.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The DrupalDateTime object.
   */
  protected function getDateTime($value) {
    if ($value instanceof TimestampItem) {
      $timestampValue = $value->get('value');
      if ($timestampValue instanceof Timestamp) {
        $value = $timestampValue->getDateTime();
      }
    }
    if ($value instanceof DateTimeItem) {
      /** @var \Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601 $date */
      $date = $value->get('value');
      $value = $date->getDateTime();
    }
    if (is_string($value) || is_int($value)) {
      $timestamp = (string) $value;
      $value = DrupalDateTime::createFromTimestamp($timestamp);
    }

    if ($value instanceof DrupalDateTime) {
      // Drupal automatically applies the correct timezone in most scenarios.
      // However, UTC time is used in certain situations, such as when dates are
      // pulled from field storage. To avoid inconsistencies, we explicitly set
      // the current user's timezone in all cases.
      $value->setTimezone(new \DateTimeZone(date_default_timezone_get()));
      return $value;
    }

    return NULL;
  }

}
