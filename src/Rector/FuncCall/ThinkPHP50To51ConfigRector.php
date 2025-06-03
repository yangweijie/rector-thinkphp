<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Convert ThinkPHP 5.0 config() calls to ThinkPHP 5.1 format with dot notation
 *
 * @see \Rector\ThinkPHP\Tests\Rector\FuncCall\ThinkPHP50To51ConfigRectorTest
 */
final class ThinkPHP50To51ConfigRector extends AbstractRector
{
    /**
     * @var array<string, string> Mapping from 5.0 config keys to 5.1 dot notation
     */
    private const CONFIG_KEY_MAP = [
        'app_debug' => 'app.app_debug',
        'app_trace' => 'app.app_trace',
        'app_status' => 'app.app_status',
        'app_multi_module' => 'app.app_multi_module',
        'auto_bind_module' => 'app.auto_bind_module',
        'default_return_type' => 'app.default_return_type',
        'default_ajax_return' => 'app.default_ajax_return',
        'default_jsonp_handler' => 'app.default_jsonp_handler',
        'var_jsonp_handler' => 'app.var_jsonp_handler',
        'default_timezone' => 'app.default_timezone',
        'default_filter' => 'app.default_filter',
        'default_lang' => 'app.default_lang',
        'class_suffix' => 'app.class_suffix',
        'controller_suffix' => 'app.controller_suffix',
        'default_module' => 'app.default_module',
        'deny_module_list' => 'app.deny_module_list',
        'default_controller' => 'app.default_controller',
        'default_action' => 'app.default_action',
        'default_validate' => 'app.default_validate',
        'empty_controller' => 'app.empty_controller',
        'use_action_prefix' => 'app.use_action_prefix',
        'action_suffix' => 'app.action_suffix',
        'auto_search_controller' => 'app.auto_search_controller',
        'url_controller_layer' => 'app.url_controller_layer',
        'var_pathinfo' => 'app.var_pathinfo',
        'pathinfo_fetch' => 'app.pathinfo_fetch',
        'pathinfo_depr' => 'app.pathinfo_depr',
        'url_html_suffix' => 'app.url_html_suffix',
        'url_common_param' => 'app.url_common_param',
        'url_param_type' => 'app.url_param_type',
        'url_route_on' => 'app.url_route_on',
        'route_complete_match' => 'app.route_complete_match',
        'route_config_file' => 'app.route_config_file',
        'url_route_must' => 'app.url_route_must',
        'url_domain_deploy' => 'app.url_domain_deploy',
        'url_domain_root' => 'app.url_domain_root',
        'url_convert' => 'app.url_convert',
        'var_method' => 'app.var_method',
        'var_ajax' => 'app.var_ajax',
        'var_pjax' => 'app.var_pjax',
        'request_cache' => 'app.request_cache',
        'request_cache_expire' => 'app.request_cache_expire',
        'request_cache_except' => 'app.request_cache_except',
        'template' => 'template',
        'view_replace_str' => 'template.tpl_replace_string',
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert ThinkPHP 5.0 config() calls to ThinkPHP 5.1 format with dot notation',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$debug = config('app_debug');
$trace = config('app_trace');
$template = config('template');
$replace = config('view_replace_str');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$debug = config('app.app_debug');
$trace = config('app.app_trace');
$template = config('template');
$replace = config('template.tpl_replace_string');
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->name instanceof Name) {
            return null;
        }

        $functionName = $this->getName($node->name);
        if ($functionName !== 'config') {
            return null;
        }

        // Check if there's a first argument and it's a string
        if (!isset($node->args[0]) || !$node->args[0]->value instanceof String_) {
            return null;
        }

        $configKey = $node->args[0]->value->value;

        // Check if this config key needs to be converted
        if (isset(self::CONFIG_KEY_MAP[$configKey])) {
            $newConfigKey = self::CONFIG_KEY_MAP[$configKey];
            $node->args[0] = new Arg(new String_($newConfigKey));
            return $node;
        }

        return null;
    }
}
