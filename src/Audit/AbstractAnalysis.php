<?php

namespace Drutiny\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;
use Symfony\Component\Yaml\Yaml;

/**
 * Audit gathered data.
 *
 * @Param(
 *  name = "expression",
 *  type = "string",
 *  default = "true",
 *  description = "The expression language to evaludate. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "not_applicable",
 *  type = "string",
 *  default = "false",
 *  description = "The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 */
abstract class AbstractAnalysis extends Audit
{
  public function configure()
  {
    $this->addParameter(
        'expression',
        static::PARAMETER_REQUIRED,
        'The expression language to evaluate. See https://symfony.com/doc/current/components/expression_language/syntax.html'
      )
      ->addParameter(
        'warning',
        static::PARAMETER_OPTIONAL,
        'The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html',
        'false'
      )
      ->addParameter(
        'not_applicable',
        static::PARAMETER_OPTIONAL,
        'The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html',
        'false'
      );
  }

  /**
   * Gather analysis data to audit.
   */
    abstract protected function gather(Sandbox $sandbox);

    final public function audit(Sandbox $sandbox)
    {
        $this->gather($sandbox);

        $expressionLanguage = $this->container->get('expression_language');

        $variables  = $sandbox->getParameterTokens();
        $this->logger->debug(__CLASS__ . ':TOKENS ' . Yaml::dump($variables));

        $expression = $sandbox->getParameter('not_applicable', 'false');
        $this->logger->debug(__CLASS__ . ':INAPPLICABILITY ' . $expression);
        if (@$expressionLanguage->evaluate($expression, $variables)) {
            return self::NOT_APPLICABLE;
        }

        $expression = $sandbox->getParameter('expression', 'true');
        $this->logger->info(__CLASS__ . ':EXPRESSION: ' . $expression);
        $output = @$expressionLanguage->evaluate($expression, $variables);
        $this->logger->info(__CLASS__ . ':EVALUATION: ' . json_encode($output));
        return $output;
    }
}
