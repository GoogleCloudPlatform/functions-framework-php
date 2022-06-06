# prerun.sh sets up the test function to use the functions framework commit
# specified by generating a `composer.json`. This makes the function `pack` buildable
# with GCF buildpacks.
#
# `pack` command example:
# pack build test-fast --builder us.gcr.io/fn-img/buildpacks/php74/builder:php74_20220531_7_4_29_RC00 --env GOOGLE_RUNTIME=php74 --env GOOGLE_FUNCTION_TARGET=declarativeHttpFunc
set -e

SCRIPT_DIR=$(realpath $(dirname $0))
cd $SCRIPT_DIR

composer install
