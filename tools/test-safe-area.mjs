import { chromium, devices } from "playwright";

const url = process.argv[2] || "http://localhost/phonesdukan/";
const profiles = [
  { name: "iphone-13", device: devices["iPhone 13"] },
  { name: "pixel-7", device: devices["Pixel 7"] },
];

let failed = false;

for (const profile of profiles) {
  const browser = await chromium.launch();
  const context = await browser.newContext({ ...profile.device });
  const page = await context.newPage();
  await page.goto(url, { waitUntil: "networkidle", timeout: 60000 });

  const metrics = await page.evaluate(() => {
    const root = document.documentElement;
    const safeTop = document.querySelector(".pd-safe-area-top");
    const bar = document.querySelector(".pd-announcement-bar");
    const track = document.querySelector(".pd-announcement-track");
    const safeAreaPx = parseFloat(getComputedStyle(root).getPropertyValue("--safe-area-top")) || 0;
    const safeRect = safeTop?.getBoundingClientRect();
    const barRect = bar?.getBoundingClientRect();
    const trackRect = track?.getBoundingClientRect();
    return {
      safeAreaPx,
      safeTopHeight: safeRect?.height ?? null,
      barTop: barRect?.top ?? null,
      trackTop: trackRect?.top ?? null,
      hasSafeAreaNode: !!safeTop,
      hasSafeAreaScript: typeof window.PDSafeArea !== "undefined",
    };
  });

  console.log(`[${profile.name}]`, metrics);

  if (!metrics.hasSafeAreaNode) {
    console.error(`FAIL [${profile.name}]: missing .pd-safe-area-top`);
    failed = true;
  }

  if (!metrics.hasSafeAreaScript) {
    console.error(`FAIL [${profile.name}]: safe-area.js not loaded`);
    failed = true;
  }

  if (metrics.trackTop !== null && metrics.trackTop < metrics.safeAreaPx - 1) {
    console.error(
      `FAIL [${profile.name}]: yellow track overlaps safe area (trackTop=${metrics.trackTop}, safeArea=${metrics.safeAreaPx})`
    );
    failed = true;
  }

  if (metrics.barTop !== null && Math.abs(metrics.barTop - metrics.safeAreaPx) > 1) {
    console.error(
      `FAIL [${profile.name}]: announcement bar not aligned below safe area (barTop=${metrics.barTop}, safeArea=${metrics.safeAreaPx})`
    );
    failed = true;
  }

  await page.screenshot({ path: `tools/safe-area-${profile.name}.png`, fullPage: false });
  await browser.close();
}

if (failed) {
  process.exit(1);
}

console.log("PASS: safe-area layout checks passed for all profiles.");
