<?php
/**
 * Tokenize a PHP file to determine dependencies.
 *
 * Very simply dependency checker -- simply grabs "use" values, and builds an 
 * array of them.
 *
 * Algorithm:
 * - Get PHP file contents
 * - Tokenize PHP file contents
 * - Iterate tokenizer results
 *   - For each T_USE, parse until ';' reached
 *     - Iterate tokens captured; all contiguous T_STRING + T_NS_SEPARATOR tokens are considered classes/namespaces
 * - Remove any dependencies matching the namespace in the file
 */

namespace Zf\Util;

/**
 * Determine PHP class dependencies for a given file.
 *
 * Usage:
 *     $deps = Dependencies::getForFile($filename);
 */
class Dependencies
{
    /**
     * Retrieves dependencies for a file
     *
     * Given a filename, this method attempts to open the file (throwing an
     * exception if it cannot), and then parses it for dependencies, returning
     * an array of classnames discovered.
     *
     * The filename may be either an absolute path, or a path relative to the 
     * include_path.
     * 
     * @param  string $filename 
     * @return array
     */
    public static function getForFile($filename)
    {
        if (!is_readable($filename)) {
            if (!$resolved = static::getPathFromIncludePath($filename)) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not locate file by name "%s"',
                    $filename
                ));
            }
            $filename = $resolved;
        }
        $tokens = token_get_all(file_get_contents($filename));
        return static::parse($tokens);
    }

    /**
     * Parses tokens from a file in order to build and return a list of 
     * dependencies.
     *
     * Dependencies are retrieved based on "use" statements in the code, as
     * compared against the discovered namespace (if any).
     * 
     * @param  array $tokens 
     * @return array Array of class names
     */
    protected static function parse(array $tokens)
    {
        $namespace       = '';
        $dependencies    = array();
        $insideNamespace = false;
        $insideUse       = false;
        $insideAlias     = false;
        $insideAs        = false;
        $currentAlias    = '';
        foreach ($tokens as $token) {
            if (is_string($token)) {
                if ($insideNamespace && ($token === ';')) {
                    $insideNamespace = false;
                }
                if ($insideUse && ($token === '(')) {
                    // Inside "use" statement from closure; short circuit early
                    $insideUse = false;
                }
                if ($insideUse && ($token === ';')) {
                    if ($insideAlias && !empty($currentAlias)) {
                        $dependencies[] = trim($currentAlias, '\\');
                    }
                    $insideUse    = false;
                    $insideAlias  = false;
                    $insideAs     = false;
                    $currentAlias = '';
                }
                if ($insideUse && ($token === ',')) {
                    if ($insideAs) {
                        $insideAs = false;
                    }
                    if ($insideAlias) {
                        if (!empty($currentAlias)) {
                            $dependencies[] = trim($currentAlias, '\\');
                        }
                        $insideAlias    = false;
                        $currentAlias   = '';
                        continue;
                    }
                }
                continue;
            }
            switch (token_name($token[0])) {
                case 'T_NAMESPACE':
                    $insideNamespace = true;
                    break;
                case 'T_USE':
                    $insideUse = true;
                    break;
                case 'T_STRING':
                case 'T_NS_SEPARATOR':
                    if ($insideNamespace) {
                        $namespace .= $token[1];
                        break;
                    }
                    if ($insideUse) {
                        if (!$insideAlias) {
                            $insideAlias = true;
                        }
                        if (!$insideAs) {
                            $currentAlias .= $token[1];
                        }
                    }
                    break;
                case 'T_AS':
                    $insideAs = true;
                default:
                    if ($insideAlias) {
                        if (!empty($currentAlias)) {
                            $dependencies[] = trim($currentAlias, '\\');
                        }
                        $insideAlias    = false;
                        $currentAlias   = '';
                    }
            }
        }

        // Normalize dependencies
        // We only want:
        // - non-empty dependencies
        // - component-level dependencies
        foreach ($dependencies as $k => $v) {
            // Empty? remove
            if (empty($v)) {
                unset($dependencies[$k]);
            }

            $segments = explode('\\', $v);

            // 2-segments or less? done, as we have a component-level
            // namespace
            if (2 >= count($segments)) {
                continue;
            }

            // Otherwise, reset by concatenating the first two segments
            $dependencies[$k] = $segments[0] . '\\' . $segments[1];
        }

        // Sort for uniques
        $dependencies = array_unique($dependencies);

        // Return early if we don't have a namespace, or if we didn't find
        // any dependencies
        if (empty($namespace) || empty($dependencies)) {
            return $dependencies;
        }

        // Remove dependencies that reference the same component
        // First, get the component-level namespace
        $namespaceSegments = explode('\\', $namespace);
        if (2 < count($namespaceSegments)) {
            $namespace = $namespaceSegments[0] . '\\' . $namespaceSegments[1];
        }
        $topLevel = $namespaceSegments[0];

        // Next, loop through the dependencies to see if any match this 
        // component namespace or the top-level namespace
        foreach ($dependencies as $index => $dep) {
            if ($dep == $namespace || $dep == $topLevel) {
                unset($dependencies[$index]);
            }
        }
        return $dependencies;
    }

    /**
     * Resolve file on include_path, returning realpath
     * 
     * @param  string $filename 
     * @return false|string Returns false if unable to resolve, path otherwise
     */
    protected static function getPathFromIncludePath($filename)
    {
        $dirs = explode(PATH_SEPARATOR, get_include_path());
        foreach ($dirs as $dir) {
            $test = $dir . DIRECTORY_SEPARATOR . $filename;
            if (is_readable($test)) {
                return $test;
            }
        }
        return false;
    }
}
