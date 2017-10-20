#!/bin/sh
export PATH=/etc/sonar-scanner/bin:"$PATH"
cd src
sonar-scanner -Dsonar.projectKey=CyberREST -Dsonar.sources=. -Dsonar.php.coverage.reportPaths=../build/logs/clover.xml -Dsonar.php.tests.reportPath=../build/logs/tests-junit.xml