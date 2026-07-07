plugins {
    id("com.android.application")
}

android {
    namespace = "com.phonesdukan.app"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.phonesdukan.app"
        minSdk = 24
        targetSdk = 35
        versionCode = ((System.currentTimeMillis() / 1000L).toInt())
        versionName = "1.0.${System.currentTimeMillis() / 1000L}"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    implementation("androidx.appcompat:appcompat:1.7.0") {
        exclude(group = "org.jetbrains.kotlin", module = "kotlin-stdlib-jdk7")
        exclude(group = "org.jetbrains.kotlin", module = "kotlin-stdlib-jdk8")
    }
    implementation("androidx.core:core-splashscreen:1.0.1")
}

configurations.configureEach {
    exclude(group = "org.jetbrains.kotlin", module = "kotlin-stdlib-jdk7")
    exclude(group = "org.jetbrains.kotlin", module = "kotlin-stdlib-jdk8")
}

val publishDebugApk = tasks.register<Copy>("publishDebugApk") {
    dependsOn("assembleDebug")
    from(layout.buildDirectory.file("outputs/apk/debug/app-debug.apk"))
    into(rootProject.file("../../public/downloads"))
    rename { "phonesdukan-app.apk" }
    doLast {
        val versionFile = rootProject.file("../../public/downloads/app-version.properties")
        val versionCode = android.defaultConfig.versionCode
        val versionName = android.defaultConfig.versionName ?: "1.0.0"
        val builtAt = System.currentTimeMillis() / 1000L
        versionFile.parentFile?.mkdirs()
        versionFile.writeText(
            buildString {
                appendLine("apkFile=phonesdukan-app.apk")
                appendLine("builtAt=$builtAt")
                appendLine("versionName=$versionName")
                appendLine("versionCode=$versionCode")
            }
        )
        println("Published APK to public/downloads/phonesdukan-app.apk ($versionName)")
    }
}

afterEvaluate {
    tasks.named("assembleDebug").configure {
        finalizedBy(publishDebugApk)
    }
}
