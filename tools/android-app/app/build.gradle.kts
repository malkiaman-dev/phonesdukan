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
        versionCode = 1
        versionName = "1.0.0"
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
}

configurations.configureEach {
    exclude(group = "org.jetbrains.kotlin", module = "kotlin-stdlib-jdk7")
    exclude(group = "org.jetbrains.kotlin", module = "kotlin-stdlib-jdk8")
}

val publishDebugApk = tasks.register<Copy>("publishDebugApk") {
    dependsOn("assembleDebug")
    from(layout.buildDirectory.file("outputs/apk/debug/app-debug.apk"))
    into(rootProject.file("../../public/downloads"))
    rename { "phonesdukan.apk" }
    doLast {
        println("Published APK to public/downloads/phonesdukan.apk")
    }
}

tasks.named("assembleDebug") {
    finalizedBy(publishDebugApk)
}
