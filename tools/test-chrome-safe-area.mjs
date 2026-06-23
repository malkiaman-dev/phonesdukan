import { chromium, devices } from "playwright";

const url = process.argv[2] || "http://127.0.0.1/phonesdukan/";
const profiles = [
  { name: "pixel-7-android", device: devices["Pixel 7"] },
  { name: "galaxy-s9", device: devices["Galaxy S9+"] },
  { name: "iphone-13", device: devices["iPhone 13"] },
];

let failed = false;

for (const profile of profiles) {
  const browser = await chromium.launch();
  const context = await browser.newContext({ ...profile.device });
  const page = await context.newPage();
  await page.goto(url, { waitUntil: "networkidle", timeout: 60000 });

  const metrics = await page.evaluate(() => {
    const chrome = document.getElementById("pd-site-chrome");
    const bar = document.querySelector(".pd-announcement-bar");
    const topBars = document.querySelector(".pd-top-bars");
    const header = document.querySelector(".pd-header-stack");
    const track = document.querySelector(".pd-announcement-track");
    const chromeRect = chrome?.getBoundingClientRect();
    const headerRect = header?.getBoundingClientRect();
    const trackRect = track?.getBoundingClientRect();
    return {
      hasChrome: !!chrome,
      announcementHidden: !topBars || getComputedStyle(topBars).display === "none",
      chromePaddingTop: chrome ? parseFloat(getComputedStyle(chrome).paddingTop) : null,
      headerTop: headerRect?.top ?? null,
      trackVisible: trackRect ? trackRect.height > 0 && getComputedStyle(bar).display !== "none" : false,
      chromeOffset: parseFloat(getComputedStyle(document.documentElement).getPropertyValue("--pd-chrome-offset")) || 0,
    };
  });

  console.log(`[${profile.name}]`, metrics);

  if (!metrics.hasChrome) {
    console.error(`FAIL [${profile.name}]: missing #pd-site-chrome`);
    failed = true;
  }

  if (!metrics.announcementHidden) {
    console.error(`FAIL [${profile.name}]: announcement bar still visible on mobile`);
    failed = true;
  }

  if (metrics.headerTop === null || metrics.headerTop < 32) {
    console.error(`FAIL [${profile.name}]: header not below status bar (headerTop=${metrics.headerTop})`);
    failed = true;
  }

  await page.screenshot({ path: `tools/chrome-safe-${profile.name}.png`, fullPage: false });
  await browser.close();
}

if (failed) {
  process.exit(1);
}

console.log("PASS: mobile header clears status bar with announcement hidden.");
