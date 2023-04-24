<?php

namespace Yarm\Visualizations\Charts;

use Balping\JsonRaw\Encoder;
use Balping\JsonRaw\Raw;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

class BaseChart
{
    /**
     * Stores the chart datasets.
     *
     * @var array
     */
    public $datasets = [];

    /**
     * Stores the dataset class to be used.
     *
     * @var object
     */
    protected $dataset = DatasetClass::class;

    /**
     * Stores the chart labels.
     *
     * @var array
     */
    public $labels = [];

    /**
     * Stores the chart options.
     *
     * @var array
     */
    public $options = [];

    /**
     * Stores the chart type.
     *
     * @var string
     */
    public $type = '';

    /**
     * Stores the available chart letters to create the ID.
     *
     * @var string
     */
    private $chartLetters = 'abcdefghijklmnopqrstuvwxyz';

    /**
     * Chart constructor.
     */
    public function __construct()
    {
        $this->id = substr(str_shuffle(str_repeat($x = $this->chartLetters, ceil(25 / strlen($x)))), 1, 25);
    }

    /**
     * Adds a new dataset to the chart.
     *
     * @param string $name
     * @param array|Collection $data
     * @return mixed
     */
    public function dataset(string $name, string $type, $data)
    {
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        $dataset = new $this->dataset($name, $type, $data);

        array_push($this->datasets, $dataset);

        return $dataset;
    }

    /**
     * Set the chart labels.
     *
     * @param array|Collection $labels
     *
     * @return self
     */
    public function labels($labels)
    {
        if ($labels instanceof Collection) {
            $labels = $labels->toArray();
        }

        $this->labels = $labels;

        return $this;
    }

    /**
     * Set the chart options.
     *
     * @param array|Collection $options
     * @param bool $overwrite
     *
     * @return self
     */
    public function options($options, bool $overwrite = false)
    {
        if (!empty($options['plugins'])) {
            $options['plugins'] = new Raw(trim(preg_replace('/\s\s+/', ' ', $options['plugins'])));
        }

        if ($options instanceof Collection) {
            $options = $options->toArray();
        }
        if ($overwrite) {
            $this->options = $options;
        } else {
            $this->options = array_replace_recursive($this->options, $options);
        }

        return $this;
    }

    /**
     * Set the chart container.
     *
     * @param string $container
     *
     * @return self
     */
    public function container(string $container = null)
    {
        if (!$container) {
            return View::make($this->container, ['chart' => $this]);
        }

        $this->container = $container;

        return $this;
    }

    /**
     * Formats the labels to be a correct output.
     *
     * @return string
     */
    public function formatLabels()
    {
        return Encoder::encode($this->labels);
    }

    /**
     * Formats the chart options.
     *
     * @param bool $strict
     *
     * @return string
     */
    public function formatOptions(bool $strict = false, bool $noBraces = false)
    {
        if (!$strict && count($this->options) === 0) {
            return '';
        }

        $options = Encoder::encode($this->options);

        return $noBraces ? substr($options, 1, -1) : $options;
    }

    /**
     * Formats the datasets for the output.
     *
     * @return string
     */
    public function formatDatasets()
    {
        return Encoder::encode(
            Collection::make($this->datasets)
                ->each(function ($dataset) {
                    $dataset->matchValues(count($this->labels));
                })
                ->map(function ($dataset) {
                    return $dataset->format($this->labels);
                })
                ->toArray()
        );
    }
}
