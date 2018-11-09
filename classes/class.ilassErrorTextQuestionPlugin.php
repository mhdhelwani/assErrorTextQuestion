<?php

include_once "./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";

/**
 * Question plugin ErrorTextQuestion
 *
 * @author Mohammed Helwani <mohammed.helwani@llz.uni-halle.de>
 * @version $Id: $
 * @ingroup    ModulesTestQuestionPool
 */
class ilassErrorTextQuestionPlugin extends ilQuestionsPlugin
{
    final function getPluginName()
    {
        return "assErrorTextQuestion";
    }

    final function getQuestionType()
    {
        return "assErrorTextQuestion";
    }

    final function getQuestionTypeTranslation()
    {
        return $this->txt($this->getQuestionType());
    }
}

?>