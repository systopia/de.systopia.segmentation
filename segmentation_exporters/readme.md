# Exporter Configurations

Exporter configurations define a configurable way to export your segment data.

This folder contains examples for such configurations. Drop the ``_example`` file ending to test them.

The system will look for such configurations here as well as in ``[civicrm.files]/persist/segmentation_exporters``. Be sure to copy *your* exporters to the latter so they don't get overwritten with upgrades.

Only files ending in ``.json`` using only simple letters (A-Z) and underscores (``_``) will be recognised as potential exporter configurations.