import { chromium, devices } from "playwright";

const baseUrl = process.argv[2] || "http://127.0.0.1/phonesdukan/";

function collectLayoutMetrics() {
  const chrome = document.getElementById("pd-site-chrome");
  const safeFill = document.querySelector(".pd-chrome-safe-fill");
  const announcement = document.querySelector(".pd-announcement-bar");
  const track = document.querySelector(".pd-announcement-track");
  const header = document.querySelector(".pd-header-stack");
  const install = document.getElementById("pd-install-app-btn");
  const root = document.documentElement;
  const chromeRect = chrome ? chrome.getBoundingClientRect() : null;
  const annRect = announcement ? announcement.getBoundingClientRect() : null;
  const trackStyle = track ? getComputedStyle(track) : null;
  const safeFillStyle = safeFill ? getComputedStyle(safeFill) : null;

  return {
    pdApp: root.getAttribute("data-pd-app"),
    padTop: getComputedStyle(root).getPropertyValue("--pd-chrome-pad-top").trim(),
    safeTop: getComputedStyle(root).getPropertyValue("--safe-area-top").trim(),
    safeFillH: safeFill ? safeFill.offsetHeight : 0,
    safeFillDisplay: safeFillStyle ? safeFillStyle.display : "missing",
    chromeTop: chromeRect ? Math.round(chromeRect.top) : null,
    annTop: annRect ? Math.round(annRect.top) : null,
    annH: annRect ? Math.round(annRect.height) : 0,
    trackH: track ? track.offsetHeight : 0,
    headerH: header ? header.offsetHeight : 0,
    trackBg: trackStyle
      ? trackStyle.backgroundImage || trackStyle.backgroundColor
      : null,
    installExists: !!install,
    installDisplay: install ? getComputedStyle(install).display : "missing",
    chromeBg: chrome ? getComputedStyle(chrome).backgroundColor : null,
  };
}

const scenarios = [
  {
    name: "app-ua",
    userAgent: devices["Pixel 7"].userAgent + " PhonesDukanApp/1.0",
    url: baseUrl,
    expectApp: true,
    mockNative: true,
  },
  {
    name: "query-param",
    userAgent: devices["Pixel 7"].userAgent,
    url: baseUrl + (baseUrl.includes("?") ? "&" : "?") + "pd_app=1",
    expectApp: true,
    mockNative: false,
  },
  {
    name: "mobile-browser",
    userAgent: devices["Pixel 7"].userAgent,
    url: baseUrl,
    expectApp: false,
    mockNative: false,
  },
];

let failed = false;

for (const scenario of scenarios) {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    ...devices["Pixel 7"],
    userAgent: scenario.userAgent,
  });

  if (scenario.mockNative) {
    await context.addInitScript(() => {
      window.PhonesDukanNative = {
        isApp: () => true,
        getStatusBarHeight: () => 24,
      };
    });
  }

  const page = await context.newPage();
  await page.goto(scenario.url, { waitUntil: "networkidle", timeout: 90000 });
  await page.waitForTimeout(500);

  const metrics = await page.evaluate(collectLayoutMetrics);
  console.log(`[${scenario.name}]`, metrics);

  const installHidden =
    !metrics.installExists ||
    metrics.installDisplay === "none" ||
    metrics.installDisplay === "hidden";

  let ok;
  if (scenario.expectApp) {
    const statusInset = scenario.mockNative ? 24 : 0;
    ok =
      metrics.pdApp === "1" &&
      metrics.padTop === statusInset + "px" &&
      metrics.safeTop === statusInset + "px" &&
      metrics.safeFillH === 0 &&
      metrics.safeFillDisplay === "none" &&
      metrics.annTop >= statusInset &&
      metrics.trackH > 0 &&
      metrics.headerH > 0 &&
      installHidden;
  } else {
    ok =
      metrics.pdApp !== "1" &&
      metrics.trackH > 0 &&
      metrics.headerH > 0 &&
      metrics.installExists &&
      metrics.installDisplay !== "none";
  }

  if (!ok) {
    console.error(`FAIL [${scenario.name}]`);
    failed = true;
  }

  await page.screenshot({
    path: `tools/app-mode-${scenario.name}.png`,
    fullPage: false,
  });
  await browser.close();
}

if (failed) {
  process.exit(1);
}

console.log("PASS: all layout scenarios");
