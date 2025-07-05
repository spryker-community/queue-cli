# Queue CLI Command

This document provides instructions for using the `queue:messages` console command. This command allows you to move messages between different queues within your Spryker application.

## Overview

The primary purpose of this command is to transfer messages from a specified source queue to a target queue. This is particularly useful for reprocessing failed messages, testing, or manually managing queue backlogs. You can move all messages or selectively filter them based on content, limit the number of messages moved, and choose whether to keep the original messages in the source queue.

## Install

Install the module with composer.

`composer require spryker-community/queue-cli`

Add new console commands to your `ConsoleDependencyProvider``

```
protected function getConsoleCommands(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
{
    return [
        // other console commands
        new QueueMessagesListConsole(),
        new QueueMessagesMoveConsole(),
    ];
}
```


---

## Move Command

`console queue:messages:move`

### Arguments

The command requires two arguments to function:

| Argument         | Description                          |
| ---------------- | ------------------------------------ |
| `source-queue`   | **(Required)** The name of the queue you want to move messages *from*. |
| `target-queue`   | **(Required)** The name of the queue you want to move messages *to*.   |

### Options

You can refine the moving process with the following optional flags:

| Option               | Shortcut | Description                                                                                               | Default |
| -------------------- | -------- | --------------------------------------------------------------------------------------------------------- | ------- |
| `--chunk-size`       | `-c`     | The number of messages to process in a single batch.                                                      | `100`   |
| `--filter`           | `-f`     | A string pattern to match against the message body. Only messages containing this string will be moved.     | `null`  |
| `--limit`            | `-l`     | The maximum total number of messages to move.                                                             | `null`  |
| `--keep`             | `-k`     | If this flag is present, messages will be copied to the target queue instead of moved (they will not be deleted from the source queue). | `false` |


## List Command

`console queue:messages:list`

### Arguments

The command requires two arguments to function:

| Argument         | Description                          |
| ---------------- | ------------------------------------ |
| `source-queue`   | **(Required)** The name of the queue you want to move messages *from*. |

### Options

You can refine the moving process with the following optional flags:

| Option               | Shortcut | Description                                                                                               | Default |
| -------------------- | -------- | --------------------------------------------------------------------------------------------------------- | ------- |
| `--filter`           | `-f`     | A string pattern to match against the message body. Only messages containing this string will be moved.     | `null`  |
| `--limit`            | `-l`     | The maximum total number of messages to move.                                                             | `null`  |

---

## Usage Examples

Here are some common scenarios demonstrating how to use the command.

### 1. Basic Move

Move all messages from `source_queue` to `target_queue`.

```bash
console queue:messages:move source_queue target_queue
```

### 2. Move a Limited Number of Messages

Move a maximum of 50 messages from `error_queue` to `retry_queue`.

```bash
console queue:messages:move error_queue retry_queue --limit=50
```

or using the shortcut:

```bash
console queue:messages:move error_queue retry_queue -l 50
```

### 3. Move Messages with a Specific Content (Filter)

Move all messages from `source_queue` to `target_queue` where the message body contains the string "customer_reference_123".

```bash
console queue:messages:move source_queue target_queue --filter="customer_reference_123"
```

or using the shortcut:

```bash
console queue:messages:move source_queue target_queue -f "customer_reference_123"
```

### 4. Copy Messages (Keep in Source Queue)

Copy all messages from `source_queue` to a `debug_queue` for inspection, without removing them from the original queue.

```bash
console queue:messages:move source_queue debug_queue --keep
```

or using the shortcut:

```bash
console queue:messages:move source_queue debug_queue -k
```

### 5. Combining Options

Copy a maximum of 200 messages containing "SKU-XYZ-01" from `product_events` to `product_events_reprocess`, processing 50 messages at a time.

```bash
console queue:messages:move product_events product_events_reprocess --filter="SKU-XYZ-01" --limit=200 --chunk-size=50 --keep
```

### 5. List messages from Queue

List 10 messages containing "Foo" from `events`.

```bash
console queue:messages:list events --filter="foo" --limit=10

