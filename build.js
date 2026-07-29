import {buildGeneral} from 'espo-extension-tools';
import cp from 'child_process';

const cwd = process.cwd();

buildGeneral({
    postComposerInstallHook: (options) => composerHook(options),
});

/**
 * @param {{dir: string}} options
 */
function composerHook(options) {
    const vendorDir = options.dir + '/vendor';

    const configFile = cwd + '/scoper.inc.php';

    const addPrefixCommand =
        `vendor/bin/php-scoper add-prefix --prefix="Espo\\Modules\\Mcp\\Vendor" ` +
        `--working-dir="${vendorDir}" --config="${configFile}" --output-dir="../vendor-build" --force`;

    cp.execSync(addPrefixCommand, {
        stdio: ['ignore', 'ignore', 'pipe'],
    });

    cp.execSync(`rm -Rf ${vendorDir}`);
    cp.execSync(`mv ${vendorDir + '-build'} ${vendorDir}`);

    const dumpAutoloadCommand =
        `composer dump-autoload --working-dir ${options.dir} --classmap-authoritative`;

    cp.execSync(dumpAutoloadCommand, {
        stdio: ['ignore', 'ignore', 'pipe'],
    });
}
