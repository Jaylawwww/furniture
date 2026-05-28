# APK Analyzer (Jay Lawrence Oracion)

CLI tool that reads an `.apk` file and prints basic metadata from the **AndroidManifest.xml**.  
It can also generate a branded **HTML report**.

## Setup

```bash
cd tools/apk-analyzer
npm install
```

## Analyze an APK (console output)

```bash
npm run analyze -- --apk "C:\path\to\your.apk"
```

## Print JSON

```bash
npm run analyze -- --apk "C:\path\to\your.apk" --json
```

## Generate an HTML report

```bash
npm run analyze -- --apk "C:\path\to\your.apk" --out "report.html"
```

Open `report.html` in your browser. The report header includes **Jay Lawrence Oracion**.

