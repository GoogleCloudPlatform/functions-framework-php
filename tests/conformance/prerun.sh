# prerun.sh sets up the test function to use the functions framework commit
# specified by generating a `composer.json`. This makes the function `pack` buildable
# with GCF buildpacks.
#
# `pack` build example command:
# pack build myfn --builder us.gcr.io/fn-img/buildpacks/php74/builder:php74_20220620_7_4_29_RC00 --env GOOGLE_RUNTIME=php74 --env GOOGLE_FUNCTION_TARGET=declarativeHttpFunc --env X_GOOGLE_TARGET_PLATFORM=gcf
FRAMEWORK_VERSION=$1

# exit when any command fails
set -e

cd $(dirname $0)

if [ -z "${FRAMEWORK_VERSION}" ]; then
    echo "Functions Framework version required as first parameter"
    exit 1
fi

if [ -z "${GITHUB_HEAD_REF}" ]; then
    GITHUB_HEAD_REF="main"
fi

echo '{
    "require": {
        "google/cloud-functions-framework": "dev-'${GITHUB_HEAD_REF}'#'${FRAMEWORK_VERSION}'",
        "cloudevents/sdk-php": "^1.0"
    }
}' >composer.json

cat composer.json
