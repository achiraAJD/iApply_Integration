SELECT * FROM Applications WHERE APP_ID = 865213
SELECT * FROM Applications WHERE APP_GRP_ID = 5899
SELECT * FROM ApplicationTypes WHERE AT_Desc LIKE '%Variation%'

SELECT * FROM LicenceNumbers where LN_LicenceNumber = 57904475
SELECT * FROM PremisesNames WHERE PN_ID = 27189
SELECT * FROM Licences WHERE LIC_ID = 258603

SELECT * FROM Entities WHERE ENT_ID IN (509348,509349,509350,509351);
SELECT * FROM EntityDetails WHERE ED_ENT_ID IN (509348,509349,509350,509351);
EXEC spWebGetEntityDetails @ED_Name1 = 'r', @ED_Surname = 'huirama', @ENT_DOB = '1985-11-26 00:00:00', @ET_Code = 'P', @ED_ABN = null, @ED_TrusteeName = NULL

SELECT * FROM Licensees WHERE LEE_ID = 33767

SELECT * FROM LicenceSystemLicences wHERE LSL_ID = 23328
SELECT * FROM LicenceSystemApplications ORDER BY LSA_ID DESC

SELECT * FROM FinancialTransaction WHERE FT_ID = 2165392
SELECT * FROM FinancialTransactionFeeItem WHERE FTFI_FT_ID = 2165392

SELECT * FROM LGO_NotificationQueue ORDER BY NQ_ID DESC

SELECT * FROM vwWebLicenceAll WHERE LN_LicenceNumber = 57000823

SELECT * FROM LicenceSystemApplications ORDER BY LSA_ID DESC;

SELECT DISTINCT

       o.name AS Object_Name,

       o.type_desc

  FROM sys.sql_modules m

       INNER JOIN

       sys.objects o

         ON m.object_id = o.object_id

 WHERE m.definition Like '%spWeblottery%';

 SELECT * FROM EntityDetails WHERE ED_ENT_ID = 509321
 SELECT * FROM EntityDetails WHERE ED_Name1 like '%achira%'

 SELECT * FROM LicenceClasses WHERE LC_Code = 'P';
 select LC_ID from vwWebLicenceClasses WHERE LC_Code = '579';


 SELECT ED_ClientID, ENT_ET_ID, *
FROM [LGS_UAT].[dbo].[Entities]
inner join EntityDetails on ENT_ID = ED_ENT_ID
where ENT_ID in ('509326', '509325')

SELECT * FROM EntityDetails WHERE ED_Name1 LIKE '%ferrari%';

SELECT * FROM LicenceSystemApplications ORDER BY LSA_ID DESC

SELECT * FROM ApplicationObjections
SELECT * FROM ObjectType

SELECT top 1 * FROM ApplicationStreams
SELECT top 1 * FROM ApplicationTypes
SELECT * FROM ApplicationStreamTypes

SELECT a.APP_ID, ast.AST_AS_ID,ast.AST_AT_ID,a.APP_AST_ID 
FROM Applications a JOIN vwApplicationStreamTypes AS ast ON a.APP_AST_ID = ast.AST_ID
WHERE a.APP_ID = 865213

SELECT * FROM Objections WHERE OBJ_ID = 18112;
SELECT * FROM ApplicationObjections wHERE AO_OBJ_ID = 18112

select AS_ID, AT_ID from vwWebDecisionAppApplicationData where app_id = 865213

-- lodge submission form test
SELECT * FROM Objections ORDER BY 1 DESC
SELECT * FROM ApplicationObjections WHERE AO_OBJ_ID = 18113
SELECT * FROM Applications WHERE APP_ID = 865213

SELECT LN_LicenceNumber FROM vwWebLicenceAll WHERE LN_ID = 27162

SELECT * FROM LotteryLicences WHERE LLIC_ID = 1
SELECT * FROM LotteryApplications WHERE LAPP_LLIC_ID = 1
SELECT TOP 1 * FROM LotteryApplicationStatus WHERE LAS_LAPP_ID = 50183

SELECT * FROM ApplicationApprovedPurposes WHERE AAP_LAPP_ID in (1,2)
SELECT * FROM ApplicationApprovedPurposes WHERE AAP_LAPP_ID = 2;

SELECT * FROM FinancialStatements WHERE FS_LAPP_ID = 63367

SELECT * FROM LotteryLicences where LLIC_LicenceNumber = 'T07/4958'
SELECT * FROM LotteryApplications WHERE LAPP_LLIC_ID = 3527 ORDER BY LAPP_ID
SELECT * FROM ApplicationApprovedPurposes  WHERE AAP_LAPP_ID IN(1,2,9898,11734,16637,17634,42075) ORDER BY AAP_LAPP_ID
SELECT * FROM FinancialStatements WHERE FS_LAPP_ID IN(1,2,9898,11734,16637,17634,42075) ORDER BY FS_LAPP_ID

SELECT * FROM LotteryApplications WHERE LAPP_ExpiryDate > GETDATE();
SELECT * FROM LotteryLicences WHERE LLIC_ID = 7977;





SELECT LLIC_ID, LLIC_LicenceNumber,LAPP_ID,LAPP_AST_ID,LAPP_LLIC_ID,LAPP_LC_ID,LAPP_ApplicNumber,LAPP_ExpiryDate,/*AAP_ID,AAP_PurposeText,*/FS_ID,FS_LAPP_ID,FS_DateReceived
FROM LotteryLicences ll
INNER JOIN LotteryApplications la ON ll.LLIC_ID = la.LAPP_LLIC_ID
--LEFT JOIN ApplicationApprovedPurposes aap ON la.LAPP_ID = aap.AAP_LAPP_ID
INNER JOIN FinancialStatements fs ON la.LAPP_ID = fs.FS_LAPP_ID
WHERE LLIC_LicenceNumber = 'CCP92' AND FS_DateReceived IS NULL --AND (LAPP_ExpiryDate > GETDATE() OR LAPP_ExpiryDate IS NULL) 
ORDER BY LAPP_ID

SELECT * FROM LotteryLicences WHERE LLIC_LicenceNumber LIKE '%CCP%'


select *,
	(select * from LicenceNumbers where LN_LIC_ID = LIC_ID for json path)
from Licences
where LIC_ID = 1

SELECT * FROM LotteryLicences WHERE LLIC_LicenceNumber = 'CCP92';

SELECT LLIC_ID,LLIC_LicenceNumber,
	(
		SELECT ll.LLIC_ID,ll.LLIC_LicenceNumber,ll.LLIC_Visible,la.LAPP_ID,la.LAPP_AST_ID,la.LAPP_LC_ID,la.LAPP_ExpiryDate,
		la.LAPP_LLIC_ID,la.LAPP_LAS_ID_CurrentLicence,la.LAPP_ApplicFee,la.LAPP_ApplicNumber,la.LAPP_ENT_ID,aap.AAP_ID,aap.AAP_PurposeText,
		la.LAPP_StatementPeriodFrom,la.LAPP_StatementPeriodTo 
		FROM LotteryLicences ll
		INNER JOIN LotteryApplications la ON ll.LLIC_ID = la.LAPP_LLIC_ID
		LEFT JOIN ApplicationApprovedPurposes aap ON la.LAPP_ID = aap.AAP_LAPP_ID 
		WHERE LAPP_LLIC_ID = 410 AND (LAPP_ExpiryDate > GETDATE() OR LAPP_ExpiryDate IS NULL)
		FOR JSON PATH
	)
FROM LotteryLicences
WHERE LLIC_ID = 410

DECLARE @LAPP_ID NUMERIC (18,0)
SELECT @LAPP_ID = LAPP_ID FROM LotteryApplications WHERE LAPP_ApplicNumber = 56929
SELECT @LAPP_ID as LAPP_ID

SELECT LAPP_ID,
	(
		SELECT FS_ID, FS_LAPP_ID, FS_ReturnNumber, FS_DateReceived, FS_GrossProceeds, FS_NettProceeds, FS_AmountDistributed, FS_Notes, FS_UpdatedOnline 
		FROM LotteryApplications la 
		INNER JOIN FinancialStatements fs ON la.LAPP_ID = fs.FS_LAPP_ID
		WHERE FS_LAPP_ID = @LAPP_ID AND FS_DateReceived IS NULL 
		FOR JSON PATH
	)
FROM LotteryApplications 
WHERE LAPP_ID = @LAPP_ID

SELECT * FROM LotteryApplications WHERE LAPP_ApplicNumber = 56929

DECLARE @LLIC_ID NUMERIC(18,0)

		SELECT @LLIC_ID = LLIC_ID FROM LotteryLicences WHERE LLIC_LicenceNumber = 'M11532'

		SELECT 
		(
			SELECT ll.LLIC_ID, ll.LLIC_LicenceNumber, la.LAPP_AST_ID, la.LAPP_LLIC_ID, la.LAPP_LC_ID, la.LAPP_ID, la.LAPP_ApplicNumber,
			la.LAPP_StatementPeriodFrom, la.LAPP_StatementPeriodTo, la.LAPP_StartDate, la.LAPP_CloseDate, la.LAPP_ExpiryDate, la.LAPP_PromotionTitle,
			la.LAPP_NoTickets, la.LAPP_TicketValue, la.LAPP_DrawDate, la.LAPP_NationalPrizeValue, la.LAPP_StatePrizeValue, aap.AAP_PurposeText 
			FROM LotteryLicences ll
			INNER JOIN LotteryApplications la ON ll.LLIC_ID = la.LAPP_LLIC_ID
			LEFT JOIN ApplicationApprovedPurposes aap ON la.LAPP_ID = aap.AAP_LAPP_ID
			LEFT JOIN FinancialStatements fs ON la.LAPP_ID = fs.FS_LAPP_ID 
			WHERE LAPP_LLIC_ID = @LLIC_ID AND FS_DateReceived IS NULL--(LAPP_ExpiryDate > GETDATE() OR LAPP_ExpiryDate IS NULL)
			FOR JSON PATH
		) AS 'LAPP_AAP_JSON'
		FROM LotteryLicences
		WHERE LLIC_ID = @LLIC_ID

SELECT * FROM LotteryLicences WHERE LLIC_LicenceNumber = 'CCP92'
select * FROM LotteryApplications where LAPP_LLIC_ID = 410
SELECT * FROM FinancialStatements WHERE FS_LAPP_ID = 410 
SELECT FS_LAPP_ID, count(FS_LAPP_ID) AS 'COUNT_FS_LAPP_ID'FROM FinancialStatements WHERE FS_LAPP_ID = 410 AND FS_DateReceived IS NULL GROUP BY FS_LAPP_ID HAVING COUNT(FS_LAPP_ID) > 1;



select * from LotteryLicences ll
inner join lotteryApplications la on ll.LLIC_ID = la.LAPP_LLIC_ID
left join FinancialStatements fs  on la.LAPP_ID = fs.FS_LAPP_ID
where LLIC_ID = 410 and fs.FS_DateReceived is null


exec spWebiApplyHelpers @Switch = 'GetLotteryLicenceAppFinanceStatementDetails', @Params = '56929'

