<?php

declare(strict_types=1);

namespace Tests\Jobs;

use App\Jobs\LinkUpdater;
use Tests\TestCase;

class LinkUpdaterTest extends TestCase
{
    public function test_update_replaces_internal(): void
    {
        $content = <<<'EOD'
# Data-driven Fixes

_This documentation is preliminary. Suggestions for improvements are welcome. Do not edit this page directly, it is
copied from an internal source._

Data-driven fixes is a feature that allows the Flutter package authors to provide data about changes that have been made
to the package's public API and uses that data to help users of the package update their code when updating to a newer
version of the package. It is not supported for other package authors.

The data is used by two tools:

- The analysis server uses it to provide quick fixes (also known as code actions) in the IDE.
- The `dart fix` command uses it to apply bulk edits to the code in a file or directory.

The purpose of this document is to describe how to write and test the data used by these tools. It contains four
sections:

- [Overview](#overview) provides an overview of how this feature is intended to be used and what kind of data can be
  expressed.
- [Examples](#examples) provides examples of how to express some common kinds of changes.
- [Reference](#reference) provides a complete definition of the data and how that data is used to update client code.
- [Testing](#testing) provides a guide for how this data can be tested.

## Overview

The data used by this feature describes the changes made to the public API of a package. It's contained in
a [data file](#data-file) in the package that defines the API.

The data is organized around the individual elements of the API, where an [element](#element) is anything that can be
referenced outside the library in which it's declared. This includes top-level declarations, such as classes and
extensions, and their members, such as methods and fields.

The changes to an element that can be described are designed to be both fine-grained and high-level.

By "fine-grained" we mean that the overall change to an API is described in terms of small, composable changes. For
example, instead of describing all of the ways in which a method's signature has changed, there are individual changes
for adding and removing individual parameters, renaming parameters, etc. This reduces the complexity of describing the
overall change.

By "high-level" we mean that the changes are described in terms of how the API changed, not in terms of what needs to be
done to client code in response to those changes. For example, if a parameter is added to a method the data would
describe the parameter and the value to be used in invocations, but doesn’t describe how to find invocations or how to
find overrides. It's the responsibility of the tools to figure out how to modify the client code in response to those
changes. This reduces the amount of work required of package authors initially and allows the tools to adjust as the
Dart language evolves, removing the need for package authors to refine the data.

The description of an [element](#element) and the [change](#change)s to that element are encapsulated in
a [transform](#transform) and the [data file](#data-file) is essentially a list of [transform](#transform)s.

The changes are applied when an analysis of the client code produces diagnostics. For example, if a method is renamed
then invocations of the old method will produce a diagnostic indicating that the method isn’t defined. This also works
if the old method is still declared and marked as being deprecated because a diagnostic is also produced in that case.
Because of that, we encourage you to add data for an API when the API is first deprecated. If the API doesn’t go through
a deprecation period, then the changes should be added to the data when the changes are made.
EOD;
        $expected = <<<'EOD'
# Data-driven Fixes

_This documentation is preliminary. Suggestions for improvements are welcome. Do not edit this page directly, it is
copied from an internal source._

Data-driven fixes is a feature that allows the Flutter package authors to provide data about changes that have been made
to the package's public API and uses that data to help users of the package update their code when updating to a newer
version of the package. It is not supported for other package authors.

The data is used by two tools:

- The analysis server uses it to provide quick fixes (also known as code actions) in the IDE.
- The `dart fix` command uses it to apply bulk edits to the code in a file or directory.

The purpose of this document is to describe how to write and test the data used by these tools. It contains four
sections:

- 'Overview' provides an overview of how this feature is intended to be used and what kind of data can be
  expressed.
- 'Examples' provides examples of how to express some common kinds of changes.
- 'Reference' provides a complete definition of the data and how that data is used to update client code.
- 'Testing' provides a guide for how this data can be tested.

## Overview

The data used by this feature describes the changes made to the public API of a package. It's contained in
a 'data file' in the package that defines the API.

The data is organized around the individual elements of the API, where an 'element' is anything that can be
referenced outside the library in which it's declared. This includes top-level declarations, such as classes and
extensions, and their members, such as methods and fields.

The changes to an element that can be described are designed to be both fine-grained and high-level.

By "fine-grained" we mean that the overall change to an API is described in terms of small, composable changes. For
example, instead of describing all of the ways in which a method's signature has changed, there are individual changes
for adding and removing individual parameters, renaming parameters, etc. This reduces the complexity of describing the
overall change.

By "high-level" we mean that the changes are described in terms of how the API changed, not in terms of what needs to be
done to client code in response to those changes. For example, if a parameter is added to a method the data would
describe the parameter and the value to be used in invocations, but doesn’t describe how to find invocations or how to
find overrides. It's the responsibility of the tools to figure out how to modify the client code in response to those
changes. This reduces the amount of work required of package authors initially and allows the tools to adjust as the
Dart language evolves, removing the need for package authors to refine the data.

The description of an 'element' and the 'change's to that element are encapsulated in
a 'transform' and the 'data file' is essentially a list of 'transform's.

The changes are applied when an analysis of the client code produces diagnostics. For example, if a method is renamed
then invocations of the old method will produce a diagnostic indicating that the method isn’t defined. This also works
if the old method is still declared and marked as being deprecated because a diagnostic is also produced in that case.
Because of that, we encourage you to add data for an API when the API is first deprecated. If the API doesn’t go through
a deprecation period, then the changes should be added to the data when the changes are made.
EOD;
        $updater = new LinkUpdater($content);
        $result = $updater->update();
        $this->assertEquals($expected, $result);
    }

    public function test_update_does_not_replace_external(): void
    {
        $content = <<<'EOD'
For more on debugging, including detailed information on `launch.json`, in VS Code refer to the VS Code
[documentation](https://code.visualstudio.com/docs/editor/debugging).

For more on debugging, including detailed information on `launch.json`, in VS Code refer to the VS Code
[documentation](http://code.visualstudio.com/docs/editor/debugging).
EOD;
        $expected = <<<'EOD'
For more on debugging, including detailed information on `launch.json`, in VS Code refer to the VS Code
[documentation](https://code.visualstudio.com/docs/editor/debugging).

For more on debugging, including detailed information on `launch.json`, in VS Code refer to the VS Code
[documentation](http://code.visualstudio.com/docs/editor/debugging).
EOD;
        $updater = new LinkUpdater($content);
        $result = $updater->update();
        $this->assertEquals($expected, $result);
    }

    public function test_update_removes_relative_internal_links(): void
    {
        $content = <<<'EOD'
"Third party code" that is not part of a Dart package must be in a subdirectory of a `third_party` directory located:

* [/third_party](../../third_party) for the Flutter Framework
* [/engine/src/flutter/third_party](../../engine/src/flutter/third_party) for the Flutter Engine.

The subdirectory in question must contain a `LICENSE` file that details the license covering that code and a `README`
describing the provenance of that code.
EOD;

        $expected = <<<'EOD'
"Third party code" that is not part of a Dart package must be in a subdirectory of a `third_party` directory located:

* '/third_party' for the Flutter Framework
* '/engine/src/flutter/third_party' for the Flutter Engine.

The subdirectory in question must contain a `LICENSE` file that details the license covering that code and a `README`
describing the provenance of that code.
EOD;
        $updater = new LinkUpdater($content);
        $result = $updater->update();
        $this->assertEquals($expected, $result);
    }

    public function test_update_removes_non_http_or_https_links(): void
    {
        $content = <<<'EOD'
Instead of manually updating dependencies in a `pubspec.yaml` file, use the [`update-packages`](packages/flutter_tools/lib/src/commands/update_packages.dart) CLI tool: , use the [`update-packages`](packages/flutter_tools/lib/src/commands/update_packages.dart) CLI tool:
EOD;
        $expected = <<<'EOD'
Instead of manually updating dependencies in a `pubspec.yaml` file, use the '`update-packages`' (packages/flutter_tools/lib/src/commands/update_packages.dart) CLI tool: , use the '`update-packages`' (packages/flutter_tools/lib/src/commands/update_packages.dart) CLI tool:
EOD;

        $updater = new LinkUpdater($content);
        $result = $updater->update();
        $this->assertEquals($expected, $result);
    }
}
