<?php declare(strict_types=1);

namespace Rector\Spaghetti\Rector;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\InlineHTML;
use Rector\FileSystemRector\Rector\AbstractFileSystemRector;
use Rector\RectorDefinition\RectorDefinition;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;

final class ExtractPhpFromSpaghettiRector extends AbstractFileSystemRector
{
    public function refactor(SmartFileInfo $smartFileInfo): void
    {
        $nodes = $this->parseFileInfoToNodes($smartFileInfo);

        // analyze here! - collect variables
        $variables = [];

        $i = 0;
        foreach ($nodes as $node) {
            if ($node instanceof InlineHTML) {
                continue;
            }

            if ($node instanceof Echo_) {
                if (count($node->exprs) === 1) {
                    // it is already variable, nothing to change
                    if ($node->exprs[0] instanceof Variable) {
                        continue;
                    }

                    ++$i;

                    $variableName = 'variable' . $i;
                    $variables[$variableName] = $node->exprs[0];

                    $node->exprs[0] = new Variable($variableName);
                }
            }
        }

        // create Controller here
        dump($variables);
        die;

        // print file
        $fileDestination = $this->createControllerFileDestination($smartFileInfo);
        $this->printNodesToFilePath($nodes, $fileDestination);
    }

    public function getDefinition(): RectorDefinition
    {
    }

    private function createControllerFileDestination(SmartFileInfo $smartFileInfo): string
    {
        $currentDirectory = dirname($smartFileInfo->getRealPath());

        return $currentDirectory . DIRECTORY_SEPARATOR . ucfirst(
            $smartFileInfo->getBasenameWithoutSuffix()
        ) . 'Controller.php';
    }
}
