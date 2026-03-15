<?php

namespace Polygen;

use Polygen\Lexer\Lexer;
use Polygen\Parser\Ast\AtomNode;
use Polygen\Parser\Ast\AtomNonTerm;
use Polygen\Parser\Ast\AtomSel;
use Polygen\Parser\Ast\AtomSub;
use Polygen\Parser\Ast\DeclNode;
use Polygen\Parser\Ast\ProdNode;
use Polygen\Parser\Ast\SeqNode;
use Polygen\Parser\Parser;

final class ModuleLoader
{
    /** @var array<string, DeclNode[]> */
    private array $cache;

    /** @var array<string, true> */
    private array $loading;

    private string $basePath;

    /**
     * @param string $basePath Base path for relative imports
     * @param array<string, DeclNode[]> $cache Shared cache for module loading
     * @param array<string, true> $loading Shared loading stack for cycle detection
     */
    public function __construct(
        string $basePath,
        array &$cache,
        array &$loading,
    ) {
        $this->basePath = $basePath;
        $this->cache = &$cache;
        $this->loading = &$loading;
    }

    /**
     * Create a root loader with empty cache and loading stack.
     */
    public static function root(string $basePath): self
    {
        $cache = [];
        $loading = [];
        return new self($basePath, $cache, $loading);
    }

    /**
     * Load a module from a relative path.
     *
     * @param string $relativePath Relative path to the module file (e.g. "grm/names.grm")
     * @param ?string $alias Alias for the module (null = global import)
     * @return DeclNode[] Declarations from the module, prefixed if alias is set
     * @throws \RuntimeException if file not found or circular import detected
     */
    public function load(string $relativePath, ?string $alias): array
    {
        $absPath = $this->resolveModulePath($relativePath);

        // Check for circular imports
        if (isset($this->loading[$absPath])) {
            throw new \RuntimeException("Circular import detected: $relativePath");
        }

        // Return from cache if already loaded
        if (isset($this->cache[$absPath])) {
            $decls = $this->cache[$absPath];
            if ($alias !== null) {
                return $this->prefixDecls($decls, $alias);
            }
            return $decls;
        }

        // Mark as loading to detect cycles
        $this->loading[$absPath] = true;

        try {
            // Read and parse the module file
            $content = file_get_contents($absPath);
            if ($content === false) {
                throw new \RuntimeException("Cannot read module file: $absPath");
            }

            $lexer = new Lexer($content);
            $tokens = $lexer->tokenize();
            $parser = new Parser($tokens);
            $parseResult = $parser->parse();

            // Load transitive imports (recursively) - share cache and loading stack
            $importedDecls = [];
            if (!empty($parseResult->imports)) {
                $childLoader = new ModuleLoader(dirname($absPath), $this->cache, $this->loading);
                foreach ($parseResult->imports as $directive) {
                    $importedDecls = array_merge(
                        $importedDecls,
                        $childLoader->load($directive->path, $directive->alias)
                    );
                }
            }

            // Merge imported symbols with this module's declarations
            $allDecls = array_merge($importedDecls, $parseResult->decls);

            // Cache the result
            $this->cache[$absPath] = $allDecls;

            // Prefix if an alias is provided
            if ($alias !== null) {
                return $this->prefixDecls($allDecls, $alias);
            }

            return $allDecls;
        } finally {
            unset($this->loading[$absPath]);
        }
    }

    /**
     * Resolve a relative module path to an absolute path.
     *
     * @param string $relativePath
     * @return string Absolute path
     * @throws \RuntimeException if path cannot be resolved
     */
    private function resolveModulePath(string $relativePath): string
    {
        $fullPath = $this->basePath . DIRECTORY_SEPARATOR . $relativePath;
        $realPath = realpath($fullPath);

        if ($realPath === false) {
            throw new \RuntimeException(
                "Module not found: $relativePath (basePath: {$this->basePath})"
            );
        }

        return $realPath;
    }

    /**
     * Prefix declarations with a module alias and rewrite internal references.
     *
     * @param DeclNode[] $decls
     * @param string $prefix Module alias (e.g. "Names")
     * @return DeclNode[] Prefixed declarations
     */
    private function prefixDecls(array $decls, string $prefix): array
    {
        // Collect all symbol names defined in this module
        $moduleSymbols = [];
        foreach ($decls as $decl) {
            $moduleSymbols[$decl->name] = true;
        }

        $prefixedDecls = [];
        foreach ($decls as $decl) {
            // Rename the declaration
            $prefixedName = "$prefix/{$decl->name}";

            // Rewrite internal references in the production
            $prefixedProd = $this->prefixProd($decl->prod, $prefix, $moduleSymbols);

            $prefixedDecls[] = new DeclNode($decl->mode, $prefixedName, $prefixedProd);
        }

        return $prefixedDecls;
    }

    /**
     * Rewrite a production, prefixing internal references.
     *
     * @param ProdNode $prod
     * @param string $prefix
     * @param array<string, true> $moduleSymbols
     * @return ProdNode
     */
    private function prefixProd(ProdNode $prod, string $prefix, array $moduleSymbols): ProdNode
    {
        $prefixedSeqs = [];
        foreach ($prod->seqs as $seq) {
            $prefixedSeqs[] = $this->prefixSeq($seq, $prefix, $moduleSymbols);
        }
        return new ProdNode($prefixedSeqs);
    }

    /**
     * Rewrite a sequence, prefixing internal references.
     *
     * @param SeqNode $seq
     * @param string $prefix
     * @param array<string, true> $moduleSymbols
     * @return SeqNode
     */
    private function prefixSeq(SeqNode $seq, string $prefix, array $moduleSymbols): SeqNode
    {
        $prefixedAtoms = [];
        foreach ($seq->atoms as $atom) {
            $prefixedAtoms[] = $this->prefixAtom($atom, $prefix, $moduleSymbols);
        }
        return new SeqNode($seq->label, $prefixedAtoms);
    }

    /**
     * Rewrite an atom, prefixing internal references.
     *
     * @param AtomNode $atom
     * @param string $prefix
     * @param array<string, true> $moduleSymbols
     * @return AtomNode
     */
    private function prefixAtom(AtomNode $atom, string $prefix, array $moduleSymbols): AtomNode
    {
        return match (true) {
            $atom instanceof AtomNonTerm => $this->prefixAtomNonTerm($atom, $prefix, $moduleSymbols),
            $atom instanceof AtomSel => new AtomSel(
                $this->prefixAtom($atom->atom, $prefix, $moduleSymbols),
                $atom->label
            ),
            $atom instanceof AtomSub => $this->prefixAtomSub($atom, $prefix, $moduleSymbols),
            default => $atom,
        };
    }

    /**
     * Rewrite an AtomNonTerm, prefixing if it references a module symbol.
     *
     * @param AtomNonTerm $atom
     * @param string $prefix
     * @param array<string, true> $moduleSymbols
     * @return AtomNonTerm
     */
    private function prefixAtomNonTerm(AtomNonTerm $atom, string $prefix, array $moduleSymbols): AtomNonTerm
    {
        // If the first segment of the path is a module symbol, prepend the prefix
        if (isset($moduleSymbols[$atom->path[0]])) {
            $newPath = [$prefix, ...$atom->path];
            return new AtomNonTerm($newPath);
        }
        return $atom;
    }

    /**
     * Rewrite an AtomSub, prefixing internal references in both declarations and production.
     *
     * @param AtomSub $atom
     * @param string $prefix
     * @param array<string, true> $moduleSymbols
     * @return AtomSub
     */
    private function prefixAtomSub(AtomSub $atom, string $prefix, array $moduleSymbols): AtomSub
    {
        // Prefix declarations inside the sub
        $prefixedDecls = [];
        foreach ($atom->decls as $decl) {
            // The declarations inside a sub are local, so we don't rename them
            // But we rewrite their internal references
            $prefixedProd = $this->prefixProd($decl->prod, $prefix, $moduleSymbols);
            $prefixedDecls[] = new DeclNode($decl->mode, $decl->name, $prefixedProd);
        }

        // Prefix the production
        $prefixedProd = $this->prefixProd($atom->prod, $prefix, $moduleSymbols);

        return new AtomSub(
            $prefixedDecls,
            $prefixedProd,
            mobile: $atom->mobile,
            unfolded: $atom->unfolded,
            selectorSub: $atom->selectorSub
        );
    }
}
