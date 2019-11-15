# Scrubtool in Elastic Beanstalk
If you intend to run this application in AWS EB, you will need to set the following environment variables.
To ensure file persistence and other features function as expected.

## Optional Environment Variables

    EFS_DNS_NAME             - Full DNS of the EFS mount. Important if you are using more than a single instance.
    NR_APPNAME               - Newrelic application name.
    NR_APPID                 - Newrelic application ID number for deployment notifications.
    NR_APM_INSTALL_KEY       - NewRelic install key for Application Monitoring.
    NR_INF_INSTALL_KEY       - NewRelic install key for Infrastructure.
