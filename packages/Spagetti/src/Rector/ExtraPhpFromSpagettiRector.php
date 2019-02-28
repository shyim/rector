<?php declare(strict_types=1);

namespace Rector\Spagetti\Rector;

use PhpParser\Lexer;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\InlineHTML;
use Rector\FileSystemRector\Contract\FileSystemRectorInterface;
use Rector\NodeTypeResolver\NodeScopeAndMetadataDecorator;
use Rector\PhpParser\Parser\Parser;
use Rector\PhpParser\Printer\FormatPerservingPrinter;
use Rector\RectorDefinition\RectorDefinition;
use Symfony\Component\Filesystem\Filesystem;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;

final class ExtraPhpFromSpagettiRector implements FileSystemRectorInterface
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @var FormatPerservingPrinter
     */
    private $formatPerservingPrinter;

    /**
     * @var NodeScopeAndMetadataDecorator
     */
    private $nodeScopeAndMetadataDecorator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        Parser $parser,
        Lexer $lexer,
        FormatPerservingPrinter $formatPerservingPrinter,
        Filesystem $filesystem,
        NodeScopeAndMetadataDecorator $nodeScopeAndMetadataDecorator
    ) {
        $this->parser = $parser;
        $this->lexer = $lexer;
        $this->formatPerservingPrinter = $formatPerservingPrinter;
        $this->nodeScopeAndMetadataDecorator = $nodeScopeAndMetadataDecorator;
        $this->filesystem = $filesystem;
    }

    public function refactor(SmartFileInfo $smartFileInfo): void
    {
        $oldStmts = $this->parser->parseFile($smartFileInfo->getRealPath());

        // needed for format preserving
        $newStmts = $this->nodeScopeAndMetadataDecorator->decorateNodesFromFile(
            $oldStmts,
            $smartFileInfo->getRealPath()
        );

        // analyze here! - collect variables

        $variables = [];

        $i = 0;
        foreach ($newStmts as $stmt) {
            if ($stmt instanceof InlineHTML) {
                continue;
            }

            if ($stmt instanceof Echo_) {
                if (count($stmt->exprs) === 1) {
                    // it is already variable, nothing to change
                    if ($stmt->exprs[0] instanceof Variable) {
                        continue;
                    }

                    ++$i;

                    $variableName = 'variable' . $i;
                    $variables[$variableName] = $stmt->exprs[0];

                    $stmt->exprs[0] = new Variable($variableName);
                }
            }
        }

        // create Controller here
        dump($variables);
        die;

        // print file
        $fileDestination = $this->createControllerFileDestination($smartFileInfo);

        $fileContent = $this->formatPerservingPrinter->printToString(
            $newStmts,
            $oldStmts,
            $this->lexer->getTokens()
        );

        $this->filesystem->dumpFile($fileDestination, $fileContent);
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
