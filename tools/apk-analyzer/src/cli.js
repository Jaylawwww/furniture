import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import ApkReader from 'adbkit-apkreader';
import yargs from 'yargs';
import { hideBin } from 'yargs/helpers';
import { renderHtmlReport } from './report.js';

function toArray(maybeArray) {
  if (maybeArray == null) return [];
  return Array.isArray(maybeArray) ? maybeArray : [maybeArray];
}

function uniqSorted(items) {
  return [...new Set(items.filter(Boolean))].sort((a, b) => String(a).localeCompare(String(b)));
}

async function readApkInfo(apkPath) {
  if (!fs.existsSync(apkPath)) {
    throw new Error(`APK not found: ${apkPath}`);
  }

  const reader = await ApkReader.open(apkPath);
  const manifest = await reader.readManifest();

  const app = manifest.application ?? {};

  const usesSdk = manifest.usesSdk ?? {};

  const usesPermissions = uniqSorted(
    toArray(manifest.usesPermissions).map((p) => p?.name).filter(Boolean),
  );

  const usesFeatures = uniqSorted(
    toArray(manifest.usesFeatures).map((f) => f?.name).filter(Boolean),
  );

  return {
    analyzedBy: 'Jay Lawrence Oracion',
    file: {
      name: path.basename(apkPath),
      path: path.resolve(apkPath),
      sizeBytes: fs.statSync(apkPath).size,
    },
    package: {
      name: manifest.package,
      versionName: manifest.versionName ?? null,
      versionCode: manifest.versionCode ?? null,
    },
    sdk: {
      minSdkVersion: usesSdk.minSdkVersion ?? null,
      targetSdkVersion: usesSdk.targetSdkVersion ?? null,
      maxSdkVersion: usesSdk.maxSdkVersion ?? null,
    },
    application: {
      label: app.label ?? null,
      icon: app.icon ?? null,
      debuggable: app.debuggable ?? null,
      allowBackup: app.allowBackup ?? null,
    },
    permissions: usesPermissions,
    features: usesFeatures,
  };
}

function formatBytes(bytes) {
  if (!Number.isFinite(bytes)) return String(bytes);
  const units = ['B', 'KB', 'MB', 'GB'];
  let n = bytes;
  let i = 0;
  while (n >= 1024 && i < units.length - 1) {
    n /= 1024;
    i += 1;
  }
  return `${n.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
}

const argv = yargs(hideBin(process.argv))
  .scriptName('apk-analyzer')
  .usage('$0 --apk <path> [--json] [--out <file.html>]')
  .option('apk', {
    type: 'string',
    demandOption: true,
    describe: 'Path to the .apk file',
  })
  .option('json', {
    type: 'boolean',
    default: false,
    describe: 'Print raw JSON output',
  })
  .option('out', {
    type: 'string',
    describe: 'Write an HTML report to this path',
  })
  .help()
  .strict()
  .parseSync();

const info = await readApkInfo(argv.apk);

if (argv.json) {
  process.stdout.write(`${JSON.stringify(info, null, 2)}\n`);
} else {
  process.stdout.write(`APK Analyzer (by ${info.analyzedBy})\n`);
  process.stdout.write(`File: ${info.file.name} (${formatBytes(info.file.sizeBytes)})\n`);
  process.stdout.write(`Package: ${info.package.name}\n`);
  process.stdout.write(`Version: ${info.package.versionName ?? '(unknown)'} (code ${info.package.versionCode ?? '?'})\n`);
  process.stdout.write(
    `SDK: min ${info.sdk.minSdkVersion ?? '?'} | target ${info.sdk.targetSdkVersion ?? '?'} | max ${
      info.sdk.maxSdkVersion ?? '-'
    }\n`,
  );
  process.stdout.write(`App label: ${info.application.label ?? '(unknown)'}\n`);
  process.stdout.write(`Permissions: ${info.permissions.length}\n`);
  for (const p of info.permissions) {
    process.stdout.write(`- ${p}\n`);
  }
}

if (argv.out) {
  const html = renderHtmlReport(info);
  const outPath = path.resolve(argv.out);
  fs.mkdirSync(path.dirname(outPath), { recursive: true });
  fs.writeFileSync(outPath, html, 'utf8');
  process.stdout.write(`\nWrote HTML report: ${outPath}\n`);
}
