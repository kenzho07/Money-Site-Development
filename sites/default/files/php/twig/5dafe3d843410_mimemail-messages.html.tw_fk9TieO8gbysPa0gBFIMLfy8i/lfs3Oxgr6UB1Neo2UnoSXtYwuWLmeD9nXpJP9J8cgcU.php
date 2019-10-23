<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* modules/contrib/mimemail/templates/mimemail-messages.html.twig */
class __TwigTemplate_619f8cb3555acaa4de0387aec92defab28697aee89f42364a3c7767d7241e0d5 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["if" => 4, "set" => 12];
        $filters = ["escape" => 7, "length" => 12, "raw" => 15];
        $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'set'],
                ['escape', 'length', 'raw'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->getSourceContext());

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo "<html>
  <head>
      <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
      ";
        // line 4
        if (($context["css"] ?? null)) {
            // line 5
            echo "      <style type=\"text/css\">
          <!--
          ";
            // line 7
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["css"] ?? null)), "html", null, true);
            echo "
         -->
      </style>
      ";
        }
        // line 11
        echo "  </head>
  <body id=\"mimemail-body\" ";
        // line 12
        if (((twig_length_filter($this->env, ($context["module"] ?? null)) > 0) && (twig_length_filter($this->env, ($context["key"] ?? null)) > 0))) {
            echo "  ";
            $context["class"] = (((("class=\"" . $this->sandbox->ensureToStringAllowed(($context["module"] ?? null))) . "-") . $this->sandbox->ensureToStringAllowed(($context["key"] ?? null))) . "\"");
            echo " ";
        }
        echo " >
    <div id=\"center\">
      <div id=\"main\">
        ";
        // line 15
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar($this->sandbox->ensureToStringAllowed(($context["body"] ?? null)));
        echo "
      </div>
    </div>
  </body>
</html>
";
    }

    public function getTemplateName()
    {
        return "modules/contrib/mimemail/templates/mimemail-messages.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  86 => 15,  76 => 12,  73 => 11,  66 => 7,  62 => 5,  60 => 4,  55 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "modules/contrib/mimemail/templates/mimemail-messages.html.twig", "C:\\Users\\TLLCM-1\\Desktop\\evc\\money-site\\modules\\contrib\\mimemail\\templates\\mimemail-messages.html.twig");
    }
}
