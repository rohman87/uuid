<?php
/**
 * This file is part of the Rhumsaa\Uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2012-2014 Ben Ramsey <http://benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Rhumsaa\Uuid\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rhumsaa\Uuid\Console\Exception;
use Rhumsaa\Uuid\Uuid;

/**
 * Provides the console command to decode UUIDs and dump information about them
 */
class DecodeCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('decode')
            ->setDescription('Decode a UUID and dump information about it')
            ->addArgument(
                'uuid',
                InputArgument::REQUIRED,
                'The UUID to decode.'
            );
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Uuid::isValid($input->getArgument('uuid'))) {
            throw new Exception('Invalid UUID (' . $input->getArgument('uuid') . ')');
        }

        $uuid = Uuid::fromString($input->getArgument('uuid'));

        $table = $this->getHelperSet()->get('table');
        $table->setLayout(TableHelper::LAYOUT_BORDERLESS);

        $table->addRows(array(
            array('encode:', 'STR:', (string) $uuid),
            array('',        'INT:', (string) $uuid->getInteger()),
        ));

        if ($uuid->getVariant() != Uuid::RFC_4122) {
            $table->addRows(array(
                array('decode:', 'variant:', 'Not an RFC 4122 UUID'),
            ));
        }
        else {
            $this->dumpUuid($table, $uuid);
        }

        $table->render($output);
    }

    protected function dumpUuid($table, $uuid)
    {
        $content = null;
        $version = 'Invalid or unknown UUID version';

        switch ($uuid->getVersion()) {
            case 1:
                $version = '1 (time and node based)';
                $content = array(
                    array('', 'content:', 'time:  ' . $uuid->getDateTime()->format('c')),
                    array('', '', 'clock: ' . $uuid->getClockSequence() . ' (usually random)'),
                    array('', '', 'node:  ' . substr(chunk_split($uuid->getNodeHex(), 2, ':'), 0, -1)),
                );
                break;
            case 2:
                $version = '2 (DCE security based)';
                break;
            case 3:
                $version = '3 (name based, MD5)';
                $content = array(
                    array('', 'content:', substr(chunk_split($uuid->getHex(), 2, ':'), 0, -1)),
                    array('', '', '(not decipherable: SHA1 message digest only)'),
                );
                break;
            case 4:
                $version = '4 (random data based)';
                $content = array(
                    array('', 'content:', substr(chunk_split($uuid->getHex(), 2, ':'), 0, -1)),
                    array('', '', '(no semantics: random data only)'),
                );
                break;
            case 5:
                $version = '5 (name based, SHA-1)';
                $content = array(
                    array('', 'content:', substr(chunk_split($uuid->getHex(), 2, ':'), 0, -1)),
                    array('', '', '(not decipherable: SHA1 message digest only)'),
                );
                break;
        }

        $table->addRows(array(
            array('decode:', 'variant:', 'RFC 4122'),
            array('',        'version:', $version),
        ));

        if ($content) {
            $table->addRows($content);
        }
    }
}
